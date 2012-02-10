<?php
namespace eZ\Publish\Core\Repository\Values\User;

use eZ\Publish\API\Repository\Values\User\User as APIUser,
    eZ\Publish\API\Repository\Values\Content\Content;

/**
 * This class represents a user value
 *
 */
class User extends APIUser
{
    /**
     * Instance of Content value object that this user encapsulates
     *
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $content;

    /**
     * Initializes new User object from provided content
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     */
    public function __construct( Content $content )
    {
        $this->content = $content;
    }

    /**
     * returns the VersionInfo for this version
     *
     * @return \eZ\Publish\API\Repository\Values\Content\VersionInfo
     */
    public function getVersionInfo()
    {
        return $this->content->getVersionInfo();
    }

    /**
     * returns a field value for the given value
     * $version->fields[$fieldDefId][$languageCode] is an equivalent call
     * if no language is given on a translatable field this method returns
     * the value of the initial language of the version if present, otherwise null.
     * On non translatable fields this method ignores the languageCode parameter.
     *
     * @param string $fieldDefIdentifier
     * @param string $languageCode
     *
     * @return mixed a primitive type or a field type Value object depending on the field type.
     */
    public function getFieldValue( $fieldDefIdentifier, $languageCode = null )
    {
        return $this->content->getFieldValue( $fieldDefIdentifier, $languageCode );
    }

    /**
     * returns the outgoing relations
     *
     * @return array an array of {@link Relation}
     */
    public function getRelations()
    {
        return $this->content->getRelations();
    }

    /**
     * This method returns the complete fields collection
     *
     * @return array an array of {@link Field}
     */
    public function getFields()
    {
        return $this->content->getFields();
    }

    /**
     * This method returns the fields for a given language and non translatable fields
     *
     * If note set the initialLanguage of the content version is used.
     *
     * @param string $languageCode
     *
     * @return array an array of {@link Field} with field identifier as keys
     */
    public function getFieldsByLanguage( $languageCode = null )
    {
        return $this->content->getFieldsByLanguage( $languageCode );
    }
}
