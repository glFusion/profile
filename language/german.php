<?php
/**
*   German language file for the Custom Profile plugin, adressing the user as (Du)
*
*   @author     Lee Garner <lee@leegarner.com>
*   @translated Siegfried Gutschi <sigi AT modellbaukalender DOT info> (Dez 2016)
*   @copyright  Copyright (c) 2009-2011 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$LANG_PROFILE = array(
'menu_title'				=> 'Erweitertes Profil',
'list_none_avail'			=> 'Keine Benutzer-Listen vorhanden.',
'list_err_unserial'			=> 'Beim Ändern der Felder ist ein Fehler aufgetreten.',
'admin_title'				=> 'Erweitertes Profil - Administration',
'add_profile_item'			=> 'Feld hinzufügen',
'add_value'					=> 'Wert hinzufügen',
'list_profiles'				=> 'Alle Felder',
'block_title'				=> 'Erweitertes Profil',
'yes'						=> 'Ja',
'no'						=> 'Nein',
'reset'						=> 'Zurücksetzen',
'reset_perms'				=> 'Berechtigungen zurücksetzen',
'search_users'				=> 'Benutzer-Suche',
'undefined'					=> 'Nicht definiert',
'empty'						=> 'Leer oder Beliebig',
'contains'					=> 'Enthält',
'field'						=> 'Feld-Name/ID',
'none'						=> 'Keine',
'q_searchable'				=> 'Suchbar?',
'q_sortable'				=> 'Sortierbar?',
'hdr_text'					=> 'Feld-Bezeichnung',
'include'					=> 'Aktivieren',
'def_sort'					=> 'Standart-Sortierung',

'hlp_fld_order'				=> 'Die Sortierung gibt an, wo sich das Feld, im Verhältnis zu anderen Feldern, in der Liste befindet. Dies kann später noch geändert werden.',
'hlp_fld_value'				=> 'Der Wert hat je nach Feld-Typ mehrere Bedeutungen:<br><ul><li>Für Textfelder ist dies der Standardwert, der in neuen Einträgen verwendet wird.</li><li>Für Kontrollkästchen ist dies der zurückgegebene Wert wenn diese aktiviert sind (normalerweise 1).</li><li>Bei Radio-Buttons und Dropdown-Listen handelt es sich um eine Zeichenfolge im Format: "value1:prompt1; value2:prompt2; ..."</li></ul>',
'hlp_fld_mask'				=> 'Gib die Datenmaske ein (z.B. "99999-9999" für eine US-Postleitzahl)',
'hlp_fld_enter_format'		=> 'Gib den Format-String ein',
'hlp_fld_enter_default'		=> 'Gib den Standardwert für dieses Feld ein',
'hlp_fld_def_option'		=> 'Gib den für die Standard-Einstellung zu verwendenden Optionsnamen ein',
'hlp_fld_def_option_name'	=> 'Gib den Standard-Optionsnamen ein',
'hlp_fld_chkbox_default'	=> 'Gib "1" für aktiviert oder "0" für deaktiviert ein.',

'orderby'					=> 'Sortierung',
'name'						=> 'Name',
'type'						=> 'Typ',
'enabled'					=> 'Aktiviert',
'show_in_profile'			=> 'Im Profil anzeigen',
'spancols'					=> 'Ohne Feld-Bezeichnung',
'required'					=> 'Erforderlich',
'msg_fld_missing'			=> '%s ist erforderlich aber leer.',
'msg_mgr_override' => 'Validation Errors overriden by Manager.',
'hlp_required'				=> 'Dieses Feld darf nicht leer sein',
'hlp_visible'				=> 'Diese Feld ist in Deinem Profil sichtbar',
'hlp_invisible'				=> 'Diese Feld ist in Deinem Profil NICHT sichtbar',
'user_reg'					=> 'Registrierung',
'readonly'					=> 'Nur-Lesen',
'invisible'					=> 'Nicht Sichtbar',
'select'					=> '-- Wähle --',
'usermenu'					=> 'Benutzer anzeigen',

'textprompt'				=> 'Feld-Bezeichnung',
'help_text'					=> 'Hilfe-Text',
'fieldname'					=> 'Feld-Name/ID',
'fieldtype'					=> 'Feld-Typ',
'inputlen'					=> 'Größe des Eingabe-Feldes',
'maxlen'					=> 'Max. Zeichen für Eingabe',
'value'						=> 'Wert',
'defvalue'					=> 'Standart-Wert / Vorgabe',
'showtime'					=> 'Zeit anzeigen',
'hourformat'				=> 'Zeit-Format',
'hour12'					=> '12-Stunden',
'hour24'					=> '24-Stunden',
'format'					=> 'Format',
'input_format'				=> 'Eingabe-Format',
'autogen'					=> 'Automatisierte Vorgabe',
'mask'						=> 'Feldmaske * Field Mask',
'stripmask'					=> 'Masken-Elemente aus Wert entfernen * Strip Mask Characters from Value',
'ent_registration'			=> 'Für Registrierung',
'pos_after'					=> 'Anzeigen nach',
'nochange'					=> 'Keine Änderung',
'first'						=> 'Erste Position',
'permissions'				=> 'Berechtigungen',
'defgroup'					=> 'Standart-Gruppe',
'month'						=> 'Monat',
'day'						=> 'Tag',
'year'						=> 'Jahr',

'fld_types'     => array(
    'text'					=> 'Text',
    'textarea'				=> 'Text-Feld',
    'checkbox'				=> 'Kontrollkästchen',
    'multicheck'			=> 'Mehrfach-Kontrollkästchen',
    'select'				=> 'Dropdown-Liste',
    'radio'					=> 'Radio-Buttons',
    'date'					=> 'Datum',
    'static'				=> 'Statisch',
    'account'				=> 'Benutzer-Konto',
),

'help'  => array(
    'list'					=> 'Erstelle, bearbeite und sortiere hier benutzerdefinierte Profil-Felder',
    'searchusers'			=> '<ul><li>Gib die Werte ein, die für die Benutzer-Suche verwendet werden sollen.</li><li>Aktiviere das Kontrollkästchen unter "Leer?" Wenn das Feld leer sein sollte, andernfalls werden alle leeren Felder ignoriert.</li></ul>',
    'lists'					=> '<ul><li>Erstelle und bearbeite Benutzer-Listen.</li><li>Kopiere den Link der Listen-Name/ID für die Platzierung in Menüs und anderen Inhalten.</li></ul>',
    'editform'				=> '<ul><li>Erstelle hier neue benutzerdefinierte Profil-Felder.</li><li>Die Sortierung gibt an, wo das Feld im Verhältnis zu anderen Feldern in den Listen erscheint.</li><li>Die Sortierung der Felder kann später jederzeit geändert werden.</li></ul>',
    'editlist'				=> '<ul><li>Definiere oder ändere die Ansicht der Benutzer-Listen.</li><li>Markiere die Felder, die aufgenommen werden sollen.</li><li>Ändere die Reihenfolge oder Spaltenüberschriften.</li></ul>',
    'resetpermform'			=> 'Setze alle Feld-Berechtigungen auf ihre Standardwerte zurück.',
),


'fieldset1'					=> 'Erweiterte Profileinstellungen',
'list_view_grp'				=> 'Sichtbar für',
'list_incl_grp'				=> 'Für Gruppe',
'listid'					=> 'Listen-Name/ID',
'title'						=> 'Bezeichnung',
'lists'						=> 'Benutzer-Listen',
'newlist'					=> 'Neue Benutzer-Liste',
'rows'						=> 'Reihen',
'columns'					=> 'Spalten',
'avatar'					=> 'Benutzer-Bild',
'checked'					=> 'Geprüft',
'unchecked'					=> 'Ungeprüft',
'any'						=> 'Irgendein',
'incl_user_stat'			=> 'Für Benutzer-Status',
'incl_exp_stat'				=> 'Für Konto-Status',
'name_format0'				=> 'Vorname Nachname',
'name_format1'				=> 'Vorname, Nachname',
'list_footer'				=> 'Hervorgehobene Einträge weisen auf Benutzer hin, die nicht in die Benutzer-Liste aufgenommen wurden.',
'save_error_title'			=> 'Erweitertes Profil konnte nicht gespeichert werden',
'missing_email'				=> 'Deine E-Mail Adresse ist nicht in Deinem Konto hinterlegt. Dies kann passieren, wenn Du dich mit Deinem Facebook-Profil angemeldet hast. Bitte aktualisiere Deine E-Mail Adresse in Deinen <a href="' . $_CONF['site_url'] . '/usersettings.php?mode=edit">Konto Einstellungen</a> um Benachrichtigungen und Abonnements zu erhalten.',
'select_date' => 'Click for a date picker',
'toggle_success' => 'Item has been updated.',
'toggle_failure' => 'Error updating item.',

// begin membership fields
'del_selected'				=> 'Ausgewählte löschen',
'export'					=> 'CSV exportieren',
'displayed'					=> 'Angezeigt',
'all_fields'				=> 'Alle Felder',
'submitter'					=> 'Einsender',
'submitted'					=> 'Eingesendet',
'orderby'					=> 'Sortierung',
'name'						=> 'Feld-Name/ID',
'fullname'					=> 'Voller Name',
'fname'						=> 'Vorname',
'lname'						=> 'Nachname',
'address1'					=> 'Adresse',
'city'						=> 'Stadt',
'state'						=> 'Land',
'zip'						=> 'Postleitzahl',
'phone'						=> 'Telefon Nr.',
'cell'						=> 'Cell',
'email'						=> 'E-Mail',
'type'						=> 'Typ',
'status'					=> 'Status',
'current'					=> 'Aktiv',
'arrears'					=> 'In Aktivierung',
'expired'					=> 'Gesperrt',
'expires'					=> 'Läuft ab',
'position'					=> 'Position',
'division'					=> 'Aufteilung',
'joined'					=> 'Beigetreten',
'select'					=> 'Wählen',
'move'						=> 'Verschieben',
'usermenu'					=> 'Zeige Benutzer',
'membertype'				=> 'Benutzer-Typ',
'expiration'				=> 'Ablauf',
'list_member_dir'			=> 'In Benutzer-Liste aufnehmen?',
'username'					=> 'Benutzer-Name',
'email_addr'				=> 'E-Mail Adresse',
'parent_uid'				=> 'Übergeordnetes Konto',
'child_accounts'			=> 'Untergeordnete Konten',
// END MEMBERSHIP FIELDS

);

$PLG_profile_MESSAGE03  = 'Es gab eine Fehler beim abrufen der Plugin-Versions-Nummer.';
$PLG_profile_MESSAGE04  = 'Es gab eine Fehler beim aktualisieren des Plugin.';
$PLG_profile_MESSAGE05  = 'Es gab eine Fehler beim aktualisieren der Plugin-Versions-Nummer.';
$PLG_profile_MESSAGE06  = 'Das Plugin ist auf dem aktuellen Stand';
$PLG_profile_MESSAGE100 = 'Fehler beim speichern des Profiles';
$PLG_profile_MESSAGE101 = 'System-Einträge können nicht bearbeitet werden.';
$PLG_profile_MESSAGE102 = 'Es wurden alle Berechtigungen zurückgesetzt.';
$PLG_profile_MESSAGE103 = 'Fehler beim Speichern des Profiles. Eventuell ein doppelter Profil-Name.';
$PLG_profile_MESSAGE104 = 'Ungültiger Feldname angegeben.';

$LANG_MYACCOUNT['pe_profileprefs'] = 'Erweitertes Profil';

/** Language strings for the plugin configuration section */
$LANG_configsections['profile'] = array(
    'label' => 'Profile',
    'title' => 'Custom Profile Configuration'
);

$LANG_configsubgroups['profile'] = array(
    'sg_main' => 'Haupteinstellungen'
);

$LANG_fs['profile'] = array(
    'fs_main' => 'Allgemein',
    'fs_lists' => 'Formulare',
    'fs_permissions' => 'Standard-Berechtigungen',
);

$LANG_confignames['profile'] = array(
    'default_permissions' => 'Standardberechtigungen ',
    'defgroup' => 'Standart-Gruppe',
    'showemptyvals' => 'Leere Felder anzeigen',
    'grace_expired' => 'Ablauf Gnadenfrist (Tage)',
    'date_format'   => 'Datums-Format',
    'list_incl_admin' => 'Admin-Konto in Benutzer-Liste',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['profile'] = array(
    0 => array('Ja' => 1, 'Nein' => 0),
    1 => array('Ja' => TRUE, 'Nein' => FALSE),
    2 => array('Wie übermittelt' => 'Sortierung', 'Nach Stimmen' => 'Sortierung'),
    3 => array('Ja' => 1, 'Nein' => 0),
    6 => array('Normal' => 'normal', 'Blöcke' => 'blocks'),
    9 => array('Nie' => 0, 'Warteschlange' => 1, 'Immer' => 2),
    10 => array('Nie' => 0, 'Immer' => 1, 'Akzeptiert' => 2, 'Abgelehnt' => 3),
    12 => array('Kein Zugriff' => 0, 'Nur-Lesen' => 2, 'Lesen & Schreiben' => 3),
    13 => array('Monat Tag Jahr' => 1, 'Tag Monat Jahr' => 2),
);


?>
