<?php
/*"******************************************************************************************************
*   (c) MulchProductions, http://www.mulchprod.de                                                       *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_scriptlet_imagehelper.php 5310 2012-11-20 20:54:08Z sidler $                             *
********************************************************************************************************/

namespace Mulchprod\Spamblock\System\Scriptlets;

use Kajona\System\System\Carrier;
use Kajona\System\System\ScriptletInterface;
use Kajona\System\System\SystemSetting;

/**
 * This scriptlet tries to obfuscate mail-addresses by reversing them.
 * The client renders them in the right order using css ltr rendering.
 * In addition, mailto links are obfuscated, too.
 *
 *
 * @package spamblock
 * @version 0.8
 * @author sidler@mulchprod.de
 */
class ScriptletSpamblock implements ScriptletInterface {


    /**
     * Processes the content.
     * Make sure to return the string again, otherwise the output will remain blank.
     *
     * @param string $strContent
     *
     * @return string
     */
    public function processContent($strContent) {

        if(_admin_ || (SystemSetting::getConfigValue("_pages_portaleditor_") == "true" && Carrier::getInstance()->getObjSession()->isAdmin() && Carrier::getInstance()->getObjSession()->getSession("pe_disable") != "true"))
            return $strContent;

        if(Carrier::getInstance()->getParam("action") == "portalEditProfile")
            return $strContent;

        if(count(getArrayPost()) > 0 && uniStrpos($strContent, "<form") !== false)
            return $strContent;

        //mailadresses in links
        $strContent = preg_replace_callback(
            '/<a [^>]*href="mailto:([^"]+)"[^>]*>(.*?)<\/a>/i',
            function($arrMatches) {
                return '<a href="#" onclick="this.href=\'mailto:\'+(\''.strrev($arrMatches[1]).'\').split(\'\').reverse().join(\'\')">'.$arrMatches[2].'</a>&#x200E;';
            },
            $strContent
        );

        //regular, plain emailadresses
        $strContent = preg_replace_callback(
            "/(mailto:\'\+\(\')?([\w\d\._\+\-]+@([a-zA-Z_\-\.]+)\.[a-zA-Z]{2,6})(\'\)\.split)?/",
            function($arrMatches) {
                //skip mailadresses already escaped before (mailtos)
                if(isset($arrMatches[3]) && $arrMatches[3] == "').split")
                    return $arrMatches[0];

                if($arrMatches[1] == "mailto:'+('")
                    return $arrMatches[0];

                $strEmail = $arrMatches[0];
                $strReturn = '<span style="unicode-bidi:bidi-override;direction:rtl;">';
                $strReturn .= strrev($strEmail);
                $strReturn .= "</span>&#x200E;";
                return $strReturn;
            },
            $strContent
        );


        return $strContent;
    }

    /**
     * Define the context the scriptlet is applied to.
     * A combination of contexts is allowed using an or-concatenation.
     * Examples:
     *   return interface_scriptlet::BIT_CONTEXT_ADMIN
     *   return interface_scriptlet::BIT_CONTEXT_ADMIN | BIT_CONTEXT_ADMIN::BIT_CONTEXT_PORTAL_ELEMENT
     *
     * @return mixed
     */
    public function getProcessingContext() {
        return ScriptletInterface::BIT_CONTEXT_PORTAL_ELEMENT;
    }

}
