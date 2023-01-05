# moodle_emp
This is a local plugin for moodle that works as an EMREX Contact Point (EMP). The EMP allows students to request the transfer of learning achievements from moodle. Achievements are transferred as signed ELMO certificates. This plugin could be used for recognition of credit oints like ects that were achieved by completing moodle courses at universities and other learning institutions.

## Installation and Setup
Install to folder moodle/local

```git clone https://github.com/ild-thl/moodle_emp.git emp```

The robrichards/xmlseclibs library is needed to sign the ELMO certificate with a XML Digital Signature.

```composer require robrichards/xmlseclibs```

In the plugin setting you also need to set the filelocation for a X.509 Certificate and a corresponding private key file and the passphrase used to create the certificate.

Admins and editing teachers need to setup courses to enable credit achievements and ELMO Transfer by accessing the EMP course settings over the course navigation bar.

## Usage

The url to send transfer requests to is \<yourmoodlesite\>/local/emp/init.php

For other EMREX clients to find this EMREX Contact Point it needs to be registered at the EMREG network. Read more here to find out how to join: https://emrex.eu/how-to-join/
