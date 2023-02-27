<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_emp;

defined('MOODLE_INTERNAL') || die;

require($CFG->dirroot . '/local/emp/vendor/autoload.php');

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

use SimpleXMLElement;

/**
 * Creates a transcript of records that attests a users achieved credits as ELMO xml.
 *
 * @package     local_emp
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class elmo_builder {
    /** Main document namespace. */
    const XMLNS_NAMESPACE = 'https://github.com/emrex-eu/elmo-schemas/tree/v1';

    /** XMLSchema namespace. */
    const XSI_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';

    /** XML namespace. */
    const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    /** XML namespace. */
    const DS_NAMESPACE = 'http://www.w3.org/2000/09/xmldsig#';

    /** Schema Location. */
    const SCHMEMALOCATION = 'https://github.com/emrex-eu/elmo-schemas/tree/v1 https://raw.githubusercontent.com/emrex-eu/elmo-schemas/v1/schema.xsd';

    /**
     * The certificate recepient, a db record of a moodle user.
     *
     * @var stdClass
     */
    private $user;

    /**
     * The db record of the issuer of the elmo certificate.
     *
     * @var stdClass
     */
    private $issuer;

    /**
     * Array of achievements (stdClass).
     *
     * @var array
     */
    private $achievements;

    /**
     * ELMO xml data.
     *
     * @var DOMDocument
     */
    private $xml;
    /**
     * Constructor.
     *
     * @param stdClass $user
     * @param stdClass $this
     * @param array $achievements
     * @param string $sessionid
     */
    public function __construct(\stdClass $user, \stdClass $issuer, array $achievements) {
        $this->user = $user;
        $this->issuer = $issuer;
        $this->achievements = $achievements;
        $this->xml = $this->build_elmo();
    }

    private function build_elmo() {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $root = $xml->createElement('elmo');
        $xml->appendChild($root);
        $xml->createAttributeNS(self::XMLNS_NAMESPACE, 'xmlns');
        $xml->createAttributeNS(self::XSI_NAMESPACE, 'xsi:schemaLocation');
        $root->setAttributeNS(self::XSI_NAMESPACE, 'schemaLocation', self::SCHMEMALOCATION);

        // Build generatedDate. Example: 2015-10-31T12:00:00+02:00.
        // Backup: Fromat '%Y-%m-%0dT%T%z'.
        $generateddate = userdate(time(), '%Y-%m-%dT%T%z');
        $generateddate = substr_replace($generateddate, ':', strlen($generateddate) - 2, 0);
        $root->appendChild($xml->createElement('generatedDate', $generateddate));

        // Build learner.
        $learner = $root->appendChild($xml->createElement('learner'));
        if (isset($this->user->country) && !empty($this->user->country)) {
            $learner->appendChild($xml->createElement('citizenship', $this->user->country));
        }
        $givennames = $this->user->firstname;
        if (isset($this->user->middlename) && !empty($this->user->middlename)) {
            $givennames .= ' ' . $this->user->middlename;
        }
        $learner->appendChild($xml->createElement('givenNames', trim($givennames)));
        $learner->appendChild($xml->createElement('familyName', $this->user->lastname));

        // // Build report.
        $report = $root->appendChild($xml->createElement('report'));
        // // Build issuer.
        $issuernode = $report->appendChild($xml->createElement('issuer'));

        if (isset($this->issuer->country)) {
            $issuernode->appendChild($xml->createElement('country', $this->issuer->country));
        }
        if (isset($this->issuer->pic)) {
            $pic = $issuernode->appendChild($xml->createElement('identifier', $this->issuer->pic));
            $pic->setAttribute('type', 'pic');
        }
        if (isset($this->issuer->erasmus)) {
            $erasmus = $issuernode->appendChild($xml->createElement('identifier', $this->issuer->erasmus));
            $erasmus->setAttribute('type', 'erasmus');
        }
        if (isset($this->issuer->schac)) {
            $schac = $issuernode->appendChild($xml->createElement('identifier', $this->issuer->schac));
            $schac->setAttribute('type', 'schac');
        }
        if (isset($this->issuer->titlede)) {
            $titlede = $issuernode->appendChild($xml->createElement('title', $this->issuer->titlede));
            $titlede->setAttributeNS(self::XML_NAMESPACE, 'lang', 'de');
        }
        if (isset($this->issuer->titleen)) {
            $titleen = $issuernode->appendChild($xml->createElement('title', $this->issuer->titleen));
            $titleen->setAttributeNS(self::XML_NAMESPACE, 'lang', 'en');
        }
        if (isset($this->issuer->url)) {
            $issuernode->appendChild($xml->createElement('url', $this->issuer->url));
        }

        // Build learningOpportunitySpecification.
        foreach ($this->achievements as $achievement) {
            $opportunity = $this->append_los($xml, $report, $achievement);
            if (!empty($achievement->parts)) {
                $haspartnode = $opportunity->appendChild($xml->createElement('hasPart'));
            }
            foreach ($achievement->parts as $part) {
                $this->append_los($xml, $haspartnode, $part);
            }
        }

        // IssueDate.
        $report->appendChild($xml->createElement('issueDate', $generateddate));

        return $xml;
    }

    protected function append_los($xml, $parent, $achievement) {
        $opportunity = $parent->appendChild($xml->createElement('learningOpportunitySpecification'));
        $localid = $opportunity->appendChild($xml->createElement('identifier', $achievement->courseid));
        $localid->setAttribute('type', 'local');
        $opportunity->appendChild($xml->createElement('title', $achievement->coursename));
        $opportunity->appendChild($xml->createElement('type', 'Course'));
        // TODO: subjectArea and iscedCode.
        // TODO: url to detailpage if isymeta plugin is installed.
        $opportunity->appendChild($xml->createElement('description', manager::xml_escape($achievement->summary)));

        $specifies = $opportunity->appendChild($xml->createElement('specifies'));
        $opportunityinstance = $specifies->appendChild($xml->createElement('learningOpportunityInstance'));
        // TODO: start.
        // TODO: date.
        $opportunityinstance->appendChild($xml->createElement('status', 'passed'));
        // TODO: grading.
        $credit = $opportunityinstance->appendChild($xml->createElement('credit'));
        $credit->appendChild($xml->createElement('scheme', $achievement->creditscheme));
        $credit->appendChild($xml->createElement('value', $achievement->creditvalue));

        if (isset($achievement->levelvalue)) {
            $level = $opportunityinstance->appendChild($xml->createElement('level'));
            $level->appendChild($xml->createElement('type', $achievement->leveltype));
            // TODO: level description.
            $level->appendChild($xml->createElement('value', $achievement->levelvalue));
        }
        $opportunityinstance->appendChild($xml->createElement('languageOfInstruction', $achievement->languageofinstruction));
        if (isset($achievement->engagementhours)) {
            $opportunityinstance->appendChild($xml->createElement('engagementHours', $achievement->engagementhours));
        }

        return $opportunity;
    }

    public function sign(): string {
        // Create a new Security object.
        $signer = new XMLSecurityDSig();
        // Use the c14n exclusive canonicalization.
        $signer->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        // Sign using SHA-256.
        $signer->addReference(
            $this->xml,
            XMLSecurityDSig::SHA256,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature')
        );

        // Create a new (private) Security key.
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'private'));
        $key->passphrase = get_config('localemp', 'pempassphrase');
        // Load the private key.
        $key->loadKey(get_config('localemp', 'keyfile'), true);

        // Sign the XML file.
        $signer->sign($key);

        // Add the associated public key to the signature.
        $signer->add509Cert(file_get_contents(get_config('localemp', 'certfile')));

        // Append the signature to the XML.
        $signer->appendSignature($this->xml->documentElement);

        // Return the signed xml as a string.
        return $this->xml->saveXML();
    }

    /**
     * Return the ELMO XML as a string.
     *
     * @return string ELMO XML
     */
    public function get_unsigned(): string {
        // Return the xml as a string.
        return $this->xml->saveXML();
    }
}
