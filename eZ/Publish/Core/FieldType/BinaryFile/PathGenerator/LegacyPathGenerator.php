<?php
/**
 * File containing the PathGenerator interface
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\FieldType\BinaryFile\PathGenerator;
use eZ\Publish\Core\FieldType\BinaryFile\PathGenerator,
    eZ\Publish\SPI\Persistence\Content\VersionInfo,
    eZ\Publish\SPI\Persistence\Content\Field;

class LegacyPathGenerator extends PathGenerator
{
    public function getStoragePathForField( Field $field, VersionInfo $versionInfo )
    {
        return md5( uniqid( microtime( true ), true ) );
    }
}