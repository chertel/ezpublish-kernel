<?php
/**
 * File containing the legacy kernel Loader class.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\MVC\Legacy\Kernel;

use eZ\Publish\Core\MVC\Legacy\Event\PostBuildKernelEvent;
use eZ\Publish\Core\MVC\Legacy\Kernel as LegacyKernel;
use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelWebHandlerEvent;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use ezpKernelHandler;
use Symfony\Component\DependencyInjection\ContainerAware;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Legacy kernel loader
 */
class Loader extends ContainerAware
{
    /**
     * @var string Absolute path to the legacy root directory (eZPublish 4 install dir)
     */
    protected $legacyRootDir;

    /**
     * @var string Absolute path to the new webroot directory (web/)
     */
    protected $webrootDir;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var URIHelper
     */
    protected $uriHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    private $buildEventsEnabled = true;

    public function __construct( $legacyRootDir, $webrootDir, EventDispatcherInterface $eventDispatcher, URIHelper $uriHelper, LoggerInterface $logger = null )
    {
        $this->legacyRootDir = $legacyRootDir;
        $this->webrootDir = $webrootDir;
        $this->eventDispatcher = $eventDispatcher;
        $this->uriHelper = $uriHelper;
        $this->logger = $logger;
    }

    /**
     * @param bool $enabled
     */
    public function setBuildEventsEnabled( $enabled = true )
    {
        $this->buildEventsEnabled = (bool)$enabled;
    }

    /**
     * @return bool
     */
    public function getBuildEventsEnabled()
    {
        return $this->buildEventsEnabled;
    }

    /**
     * Builds up the legacy kernel and encapsulates it inside a closure, allowing lazy loading.
     *
     * @param \ezpKernelHandler|\Closure A kernel handler instance or a closure returning a kernel handler instance
     *
     * @return \Closure
     */
    public function buildLegacyKernel( $legacyKernelHandler )
    {
        $legacyRootDir = $this->legacyRootDir;
        $webrootDir = $this->webrootDir;
        $eventDispatcher = $this->eventDispatcher;
        $logger = $this->logger;
        $that = $this;
        return function () use ( $legacyKernelHandler, $legacyRootDir, $webrootDir, $eventDispatcher, $logger, $that )
        {
            if ( LegacyKernel::hasInstance() )
            {
                return LegacyKernel::instance();
            }

            if ( $legacyKernelHandler instanceof \Closure )
                $legacyKernelHandler = $legacyKernelHandler();
            $legacyKernel = new LegacyKernel( $legacyKernelHandler, $legacyRootDir, $webrootDir, $logger );

            if ( $that->getBuildEventsEnabled() )
            {
                $eventDispatcher->dispatch(
                    LegacyEvents::POST_BUILD_LEGACY_KERNEL,
                    new PostBuildKernelEvent( $legacyKernel, $legacyKernelHandler )
                );
            }

            return $legacyKernel;
        };
    }

    /**
     * Builds up the legacy kernel web handler and encapsulates it inside a closure, allowing lazy loading.
     *
     * @param string $webHandlerClass The legacy kernel handler class to use
     * @param array $defaultLegacyOptions Hash of options to pass to the legacy kernel handler
     *
     * @return \Closure
     */
    public function buildLegacyKernelHandlerWeb( $webHandlerClass, array $defaultLegacyOptions = array() )
    {
        $legacyRootDir = $this->legacyRootDir;
        $webrootDir = $this->webrootDir;
        $uriHelper = $this->uriHelper;
        $eventDispatcher = $this->eventDispatcher;
        $container = $this->container;
        $that = $this;

        return function () use ( $legacyRootDir, $webrootDir, $container, $defaultLegacyOptions, $webHandlerClass, $uriHelper, $eventDispatcher, $that )
        {
            static $webHandler;
            if ( !$webHandler instanceof ezpKernelHandler )
            {
                chdir( $legacyRootDir );

                $legacyParameters = new ParameterBag( $defaultLegacyOptions );
                $legacyParameters->set( 'service-container', $container );
                $request = $container->get( 'request' );

                if ( $that->getBuildEventsEnabled() )
                {
                    // PRE_BUILD_LEGACY_KERNEL for non request related stuff
                    $eventDispatcher->dispatch( LegacyEvents::PRE_BUILD_LEGACY_KERNEL, new PreBuildKernelEvent( $legacyParameters ) );

                    // Pure web stuff
                    $eventDispatcher->dispatch(
                        LegacyEvents::PRE_BUILD_LEGACY_KERNEL_WEB,
                        new PreBuildKernelWebHandlerEvent( $legacyParameters, $request )
                    );
                }

                $interfaces = class_implements( $webHandlerClass );
                if ( !isset( $interfaces['ezpKernelHandler'] ) )
                    throw new \InvalidArgumentException( 'A legacy kernel handler must be an instance of ezpKernelHandler.' );

                $webHandler = new $webHandlerClass( $legacyParameters->all() );
                // Fix up legacy URI for global use cases (i.e. using runCallback()).
                $uriHelper->updateLegacyURI( $request );
                chdir( $webrootDir );
            }

            return $webHandler;
        };
    }

    /**
     * Builds legacy kernel handler CLI
     *
     * @return CLIHandler
     */
    public function buildLegacyKernelHandlerCLI()
    {
        $legacyRootDir = $this->legacyRootDir;
        $webrootDir = $this->webrootDir;
        $eventDispatcher = $this->eventDispatcher;
        $container = $this->container;
        $that = $this;

        return function () use ( $legacyRootDir, $webrootDir, $container, $eventDispatcher, $that )
        {
            static $cliHandler;
            if ( !$cliHandler instanceof ezpKernelHandler )
            {
                chdir( $legacyRootDir );

                $legacyParameters = new ParameterBag( $container->getParameter( 'ezpublish_legacy.kernel_handler.cli.options' ) );
                if ( $that->getBuildEventsEnabled() )
                {
                    $eventDispatcher->dispatch( LegacyEvents::PRE_BUILD_LEGACY_KERNEL, new PreBuildKernelEvent( $legacyParameters ) );
                }

                $cliHandler = new CLIHandler( $legacyParameters->all(), $container->get( 'ezpublish.siteaccess' ), $container );
                chdir( $webrootDir );
            }

            return $cliHandler;
        };
    }

    /**
     * Builds the legacy kernel handler for the tree menu in admin interface.
     *
     * @return \Closure A closure returning an \ezpKernelTreeMenu instance.
     */
    public function buildLegacyKernelHandlerTreeMenu()
    {
        return $this->buildLegacyKernelHandlerWeb(
            $this->container->getParameter( 'ezpublish_legacy.kernel_handler.treemenu.class' ),
            array(
                'use-cache-headers'    => false,
                'use-exceptions'       => true
            )
        );
    }
}
