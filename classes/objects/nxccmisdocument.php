<?php
/**
 * Definition of nxcCMISDocument class
 *
 * Created on: <25-Apr-2009 21:00:11 vd>
 *
 * COPYRIGHT NOTICE: Copyright (C) 2001-2009 NXC AS
 * SOFTWARE LICENSE: GNU General Public License v2.0
 * NOTICE: >
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of version 2.0  of the GNU General
 *   Public License as published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of version 2.0 of the GNU General
 *   Public License along with this program; if not, write to the Free
 *   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *   MA 02110-1301, USA.
 */

/**
 * Definition of CMIS document class
 *
 * @file nxccmisdocument.php
 */

//include_once( eZExtension::baseDirectory() . '/nxc_cmisclient/classes/objects/nxccmisbaseobject.php' );

class nxcCMISDocument extends nxcCMISBaseObject
{

    /**
     * Size of the content
     *
     * @var string
     */
    protected $Size = null;

    /**
     *
     *
     * @var string
     */
    protected $EnclosureUri = null;

    /**
     * Uri to edit content stream
     *
     * @var string
     */
    protected $EditMediaUri = null;

    /**
     * Uri to fetch all versions
     *
     * @var string
     */
    protected $AllVersionsUri = null;

    /**
     * Uri to fetch content stream
     *
     * @var string
     */
    protected $StreamUri = null;

    /**
     * Content stream file name
     *
     * @var string
     */
    protected $ContentStreamFileName = null;

    /**
     * Content
     *
     * @var byte[]
     */
    protected $Content = null;

    /**
     * @reimp
     */
    public function __construct( SimpleXMLElement $entry = null )
    {
        parent::__construct( $entry );

        $this->BaseType = 'document';
    }

    /**
     * @reimp
     */
    public function setFields( $entry )
    {
        if ( !is_object( $entry ) )
        {
            return;
        }

        parent::setFields( $entry );

        $size = nxcCMISUtils::getXMLValue( $entry, nxcCMISUtils::getVersionSpecificProperty( 'contentStreamLength' ) );
        $this->Size = $size ? number_format( $size / 1000, 2, '.', ',' ) . ' K' : null;
        $this->DocType = (string) nxcCMISUtils::getXMLValue( $entry, nxcCMISUtils::getVersionSpecificProperty( 'contentStreamMimeType' ) );

        $this->EnclosureUri = nxcCMISUtils::getEncodedUri( nxcCMISUtils::getHostlessUri( nxcCMISUtils::getLinkUri( $entry, 'enclosure' ) ) );
        $this->EditMediaUri = nxcCMISUtils::getEncodedUri( nxcCMISUtils::getHostlessUri( nxcCMISUtils::getLinkUri( $entry, 'edit-media' ) ) );
        $this->AllVersionsUri = nxcCMISUtils::getEncodedUri( nxcCMISUtils::getHostlessUri( nxcCMISUtils::getLinkUri( $entry, 'allversions' ) ) );

        $content = nxcCMISUtils::getValue( $entry, 'content' );
        $this->StreamUri = $content ? nxcCMISUtils::getEncodedUri( nxcCMISUtils::getHostlessUri( nxcCMISUtils::getXMLAttribute( $content , 'src' ) ) ) : '';
        $this->ContentStreamFileName = (string) nxcCMISUtils::getXMLValue( $entry, nxcCMISUtils::getVersionSpecificProperty( 'contentStreamFileName' ) );
    }

    /**
     * @reimp
     */
    public static function definition()
    {
        $parentDef = parent::definition();
        $currentDef = array( 'function_attributes' => array_merge( $parentDef['function_attributes'], array( 'size' => 'getSize',
                                                                                                             'content' => 'getContent',
                                                                                                             'enclosure_uri' => 'getEnclosureUri',
                                                                                                             'edit_media_uri' => 'getEditMediaUri',
                                                                                                             'all_versions_uri' => 'getAllVersionsUri',
                                                                                                             'stream_uri' => 'getStreamUri'
                                                                                                             ) ) );

        return $currentDef;
    }

    /**
     * @return Enclosure uri
     */
    public function getEnclosureUri()
    {
        return $this->EnclosureUri;
    }

    /**
     * @return Edit media uri
     */
    public function getEditMediaUri()
    {
        return $this->EditMediaUri;
    }

    /**
     * @return Stream uri
     */
    public function getStreamUri()
    {
        return $this->StreamUri;
    }

    /**
     * @return All versions uri
     */
    public function getAllVersionsUri()
    {
        return $this->AllVersionsUri;
    }

    /**
     * @reimp
     */
    public function setDocType( $docType )
    {
        $this->DocType = $docType;
    }

    /**
     * @reimp
     */
    public function getDocType()
    {
        return $this->DocType;
    }

    /**
     * Provides content. If it does not exist fetch from repository
     *
     * @return byte[] Object content
     */
    public function getContent( $force = false )
    {
        if ( $force or !$this->Content )
        {
            $decodedUri = nxcCMISUtils::getDecodedUri( $this->StreamUri );
            $this->Content = nxcCMISUtils::invokeService( $decodedUri );
        }
        return $this->Content;
    }

    /**
     * Sets content \a $content
     */
    public function setContent( $content )
    {
        $this->Content = $content;
    }

    /**
     * @return string Size
     */
    public function getSize()
    {
        return $this->Size;
    }

    /**
     * @reimp
     */
    public function getClassIdentifier()
    {
        return strstr( $this->DocType, 'image' ) !== false ? 'image' : ( strstr( $this->DocType, 'text' ) !== false ? 'content' : 'file' );
    }

    /**
     * @return string Content file name
     */
    public function getContentStreamFileName()
    {
        return $this->ContentStreamFileName;
    }

    /**
     * @reimp
     */
    public function update()
    {
        if ( !parent::update() )
        {
            return false;
        }

        // Clear content to update it later in getContent()
        $this->Conetnt = null;
    }

    /**
     * @reimp
     */
    public function store( $parentChildrenUri = false )
    {
        $uri = $parentChildrenUri;
        $method = 'POST';
        $contentStream = $this->Content;

        // For new object in repository SelfUri must not exist
        $newObject = !$this->SelfUri;

        if ( !$newObject )
        {
            $uri = nxcCMISUtils::getDecodedUri( $this->SelfUri );
            $method = 'PUT';
        }

        $doc = nxcCMISUtils::createDocument();
        $root = nxcCMISUtils::createRootNode( $doc, 'entry' );
        $doc->appendChild( $root );
        $title = $doc->createElement( 'title', nxcCMISUtils::escapeXMLEntries( $this->Title ) );
        $root->appendChild( $title );
        $summary = $doc->createElement( 'summary', nxcCMISUtils::escapeXMLEntries( $this->Summary ) );
        $root->appendChild( $summary );

        if ( !empty( $contentStream ) )
        {
            $stream = strpos( $this->DocType, 'text' ) === false ? base64_encode( $contentStream ) : $contentStream;
            $content = $doc->createElement( 'content', $stream );
            $content->setAttribute( 'type', $this->DocType );
            $root->appendChild( $content );
        }

        $object = $doc->createElement( nxcCMISUtils::getVersionSpecificValue( 'cmisra:object' ) );
        $root->appendChild( $object );
        $properties = $doc->createElement( 'cmis:properties' );
        $object->appendChild( $properties );
        $objectTypeId = $doc->createElement( 'cmis:propertyId' );
        $objectTypeId->setAttribute( nxcCMISUtils::getVersionSpecificValue( 'propertyDefinitionId' ), nxcCMISUtils::getVersionSpecificValue( 'cmis:objectTypeId' ) );
        $properties->appendChild( $objectTypeId );
        $value = $doc->createElement( 'cmis:value', nxcCMISUtils::getVersionSpecificValue( 'cmis:' ) . 'document' ); // @TODO: Hardcoded value!!!
        $objectTypeId->appendChild( $value );

        $xml = $doc->saveXML();
        $response = nxcCMISUtils::invokeService( $uri, $method, nxcCMISUtils::createHeaders( strlen( $xml ) ), $xml );

        if ( is_bool( $response ) )
        {
            return $response;
        }

        $entry = nxcCMISUtils::fetchEntry( $response );
        $this->setFields( $entry );

        // Check if content stream has not been updated
        if ( !$newObject and !empty( $contentStream ) and $this->EditMediaUri and $this->getContent( true ) != $contentStream )
        {
            // Put content stream to EditMediaUri. Use setContentStream service
            $response = nxcCMISUtils::invokeService( nxcCMISUtils::getDecodedUri( $this->EditMediaUri ), 'PUT', nxcCMISUtils::createHeaders( strlen( $contentStream ), $this->DocType ), $contentStream );
        }

        return true;
    }

    /**
     * @reimp
     */
    public function remove()
    {
        $response = nxcCMISUtils::invokeService( nxcCMISUtils::getDecodedUri( $this->SelfUri ), 'DELETE' );

        return ( is_bool( $response ) and $response );
    }
}
?>