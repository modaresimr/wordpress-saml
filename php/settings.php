<?php

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

require_once "_toolkit_loader.php";

use OneLogin\Saml2\Constants;

$posible_nameidformat_values = array(
    'unspecified' => Constants::NAMEID_UNSPECIFIED,
    'emailAddress' => Constants::NAMEID_EMAIL_ADDRESS,
    'transient' => Constants::NAMEID_TRANSIENT,
    'persistent' => Constants::NAMEID_PERSISTENT,
    'entity' => Constants::NAMEID_ENTITY,
    'encrypted' => Constants::NAMEID_ENCRYPTED,
    'kerberos' => Constants::NAMEID_KERBEROS,
    'x509subjecname' => Constants::NAMEID_X509_SUBJECT_NAME,
    'windowsdomainqualifiedname' => Constants::NAMEID_WINDOWS_DOMAIN_QUALIFIED_NAME
);
$posible_requestedauthncontext_values = array(
    'unspecified' => Constants::AC_UNSPECIFIED,
    'password' => Constants::AC_PASSWORD,
    'passwordprotectedtransport' => "urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport",
    'x509' => Constants::AC_X509,
    'smartcard' => Constants::AC_SMARTCARD,
    'kerberos' => Constants::AC_KERBEROS,
);


$opt['strict'] = get_option('onelogin_saml_advanced_settings_strict_mode', 'on');
$opt['debug'] = get_option('onelogin_saml_advanced_settings_debug', 'on');
$opt['sp_entity_id'] = get_option('onelogin_saml_advanced_settings_sp_entity_id', 'php-saml');

$opt['nameIdEncrypted'] = get_option('onelogin_saml_advanced_settings_nameid_encrypted', false);
$opt['authnRequestsSigned'] = get_option('onelogin_saml_advanced_settings_authn_request_signed', false);
$opt['logoutRequestSigned'] = get_option('onelogin_saml_advanced_settings_logout_request_signed', false);
$opt['logoutResponseSigned'] = get_option('onelogin_saml_advanced_settings_logout_response_signed', false);
$opt['wantMessagesSigned'] = get_option('onelogin_saml_advanced_settings_want_message_signed', false);
$opt['wantAssertionsSigned'] = get_option('onelogin_saml_advanced_settings_want_assertion_signed', false);
$opt['wantAssertionsEncrypted'] = get_option('onelogin_saml_advanced_settings_want_assertion_encrypted', false);

$nameIDformat = get_option('onelogin_saml_advanced_nameidformat', 'unspecified');
$opt['NameIDFormat'] = $posible_nameidformat_values[$nameIDformat];


$requested_authncontext_values = get_option('onelogin_saml_advanced_requestedauthncontext', array());
if ((is_array($requested_authncontext_values) && empty(array_filter($requested_authncontext_values))) || empty($requested_authncontext_values)) {
    $opt['requestedAuthnContext'] = false;
} else {
    $opt['requestedAuthnContext'] = array();
    foreach ($requested_authncontext_values as $value) {
        if (isset($posible_requestedauthncontext_values[$value])) {
            $opt['requestedAuthnContext'][] = $posible_requestedauthncontext_values[$value];
        }
    }
}

$acs_endpoint = get_option('onelogin_saml_alternative_acs', false)
    ? plugins_url('alternative_acs.php', dirname(__FILE__))
    : add_query_arg(['saml_acs' => ''], wp_login_url());

$settings = array(

    'strict' => $opt['strict'] == 'on' ? true : false,
    'debug' => $opt['debug'] == 'on' ? true : false,

    'sp' => array(
        'entityId' => (!empty($opt['sp_entity_id']) ? $opt['sp_entity_id'] : 'php-saml'),
        'assertionConsumerService' => array(
            'url' => $acs_endpoint
        ),
        'singleLogoutService' => array(
            'url' => add_query_arg(['saml_sls' => ''], wp_login_url())
        ),
        'NameIDFormat' => $opt['NameIDFormat'],
        'x509cert' => get_option('onelogin_saml_advanced_settings_sp_x509cert'),
        'privateKey' => get_option('onelogin_saml_advanced_settings_sp_privatekey'),
    ),

    'idp' => array(
        'entityId' => get_option('onelogin_saml_idp_entityid'),
        'singleSignOnService' => array(
            'url' => get_option('onelogin_saml_idp_sso'),
        ),
        'singleLogoutService' => array(
            'url' => get_option('onelogin_saml_idp_slo'),
        ),
        'x509cert' => get_option('onelogin_saml_idp_x509cert'),
    ),

    'security' => array(
        'signMetadata' => false,
        'nameIdEncrypted' => $opt['nameIdEncrypted'] == 'on' ? true : false,
        'authnRequestsSigned' => $opt['authnRequestsSigned'] == 'on' ? true : false,
        'logoutRequestSigned' => $opt['logoutRequestSigned'] == 'on' ? true : false,
        'logoutResponseSigned' => $opt['logoutResponseSigned'] == 'on' ? true : false,
        'wantMessagesSigned' => $opt['wantMessagesSigned'] == 'on' ? true : false,
        'wantAssertionsSigned' => $opt['wantAssertionsSigned'] == 'on' ? true : false,
        'wantAssertionsEncrypted' => $opt['wantAssertionsEncrypted'] == 'on' ? true : false,
        'wantNameId' => false,
        'requestedAuthnContext' => $opt['requestedAuthnContext'],
        'relaxDestinationValidation' => true,
        "allowRepeatAttributeName" => true,
        'lowercaseUrlencoding' => get_option('
            onelogin_saml_advanced_idp_lowercase_url_encoding', false),
        'signatureAlgorithm' => get_option('onelogin_saml_advanced_signaturealgorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1'),
        'digestAlgorithm' => get_option('onelogin_saml_advanced_digestalgorithm', 'http://www.w3.org/2000/09/xmldsig#sha1'),
    )
);
