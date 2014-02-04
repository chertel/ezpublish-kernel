<?php
/**
 * File containing the eZ\Publish\Core\Repository\Permission\SearchService class.
 *
 * @copyright Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Repository\Permission;

use eZ\Publish\API\Repository\SearchService as SearchServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Repository as RepositoryInterface;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\SPI\Persistence\Content\Search\Handler;

/**
 * Search service
 *
 * @package eZ\Publish\Core\Repository\Permission
 */
class SearchService implements SearchServiceInterface
{
    /**
     * @var PermissionsService
     */
    protected $permissionsService;

    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    protected $innerSearchService;

    /**
     * Setups Permission handling of Search service
     *
     * @param PermissionsService $permissionsService
     * @param \eZ\Publish\API\Repository\SearchService $innerSearchService
     */
    public function __construct(
        PermissionsService $permissionsService,
        SearchServiceInterface $innerSearchService
    )
    {
        $this->permissionsService = $permissionsService;
        $this->innerSearchService = $innerSearchService;
    }

    /**
     * Finds content objects for the given query.
     *
     * @todo define structs for the field filters
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if query is not valid
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param array $fieldFilters - a map of filters for the returned fields.
     *        Currently supported: <code>array("languages" => array(<language1>,..))</code>.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    public function findContent( Query $query, array $fieldFilters = array() )
    {
        $query = clone $query;
        $query->filter = $query->filter ?: new Criterion\MatchAll();
        if ( !$this->permissionsService->addPermissionsCriterion( $query->filter ) )
        {
            return new SearchResult( array( 'time' => 0, 'totalCount' => 0 ) );
        }

        return $this->innerSearchService->findContent( $query );
    }

    /**
     * Performs a query for a single content object
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the object was not found by the query or due to permissions
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if criterion is not valid
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if there is more than one result matching the criterions
     *
     * @todo define structs for the field filters
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $filter
     * @param array $fieldFilters - a map of filters for the returned fields.
     *        Currently supported: <code>array("languages" => array(<language1>,..))</code>.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function findSingle( Criterion $filter, array $fieldFilters = array() )
    {
        if ( !$this->permissionsService->addPermissionsCriterion( $filter ) )
        {
            throw new NotFoundException( 'Content', '*' );
        }

        return $this->innerSearchService->findSingle( $filter, $fieldFilters );
    }

    /**
     * Suggests a list of values for the given prefix
     *
     * @param string $prefix
     * @param string[] $fieldPaths
     * @param int $limit
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $filter
     */
    public function suggest( $prefix, $fieldPaths = array(), $limit = 10, Criterion $filter = null )
    {

    }
}
