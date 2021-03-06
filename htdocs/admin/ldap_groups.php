<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/admin/ldap_groups.php
 *     	\ingroup    ldap
 *		\brief      Page to setup LDAP synchronization for groups
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ldap.lib.php");

$langs->load("admin");
$langs->load("errors");

if (!$user->admin)
  accessforbidden();
  
$action = GETPOST("action");


/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$error=0;
	$db->begin();
	
	if (! dolibarr_set_const($db, 'LDAP_GROUP_DN',GETPOST("group"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_GROUP_OBJECT_CLASS',GETPOST("objectclass"),'chaine',0,'',$conf->entity)) $error++;

	if (! dolibarr_set_const($db, 'LDAP_GROUP_FIELD_FULLNAME',GETPOST("fieldfullname"),'chaine',0,'',$conf->entity)) $error++;
	//if (! dolibarr_set_const($db, 'LDAP_GROUP_FIELD_NAME',$_POST["fieldname"],'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_GROUP_FIELD_DESCRIPTION',GETPOST("fielddescription"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_GROUP_FIELD_GROUPMEMBERS',GETPOST("fieldgroupmembers"),'chaine',0,'',$conf->entity)) $error++;

	// This one must be after the others
    $valkey='';
    $key=GETPOST("key");
    if ($key) $valkey=$conf->global->$key;
    if (! dolibarr_set_const($db, 'LDAP_KEY_GROUPS',$valkey,'chaine',0,'',$conf->entity)) $error++;

	if (! $error)
  	{
  		$db->commit();
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}



/*
 * View
 */

llxHeader('',$langs->trans("LDAPSetup"),'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("LDAPSetup"),$linkback,'setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg.='<div class="error">'.$langs->trans("LDAPFunctionsNotAvailableOnPHP").'</div>';  ;
}

dol_fiche_head($head, 'groups', $langs->trans("LDAPSetup"));


print $langs->trans("LDAPDescGroups").'<br>';
print '<br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$form=new Form($db);

print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeGroups").'</td>';
print "</tr>\n";

// DN pour les groupes
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPGroupDn").'</span></td><td>';
print '<input size="48" type="text" name="group" value="'.$conf->global->LDAP_GROUP_DN.'">';
print '</td><td>'.$langs->trans("LDAPGroupDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPGroupObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_GROUP_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPGroupObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';
print '<br>';
print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("LDAPDolibarrMapping").'</td>';
print '<td colspan="2">'.$langs->trans("LDAPLdapMapping").'</td>';
print '<td align="right">'.$langs->trans("LDAPNamingAttribute").'</td>';
print "</tr>\n";

// Filtre

// Common name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_GROUP_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldCommonNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_GROUP_FIELD_FULLNAME"'.(($conf->global->LDAP_KEY_GROUPS && $conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_GROUP_FIELD_FULLNAME)?' checked="checked"':'')."></td>";
print '</tr>';

// Name
/*$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_GROUP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_GROUP_FIELD_NAME.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_GROUP_FIELD_NAME?' checked="checked"':'')."></td>";
print '</tr>';
*/

// Description
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_GROUP_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_GROUP_FIELD_DESCRIPTION"'.(($conf->global->LDAP_KEY_GROUPS && $conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_GROUP_FIELD_DESCRIPTION)?' checked="checked"':'')."></td>";
print '</tr>';

// User group
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldGroupMembers").'</td><td>';
print '<input size="25" type="text" name="fieldgroupmembers" value="'.$conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS.'">';
print '</td><td>'.$langs->trans("LDAPFieldGroupMembersExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_GROUP_FIELD_GROUPMEMBERS"'.(($conf->global->LDAP_KEY_GROUPS && $conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS)?' checked="checked"':'')."></td>";
print '</tr>';


$var=!$var;
print '<tr '.$bc[$var].'><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table>';

print '</form>';

print '</div>';

print info_admin($langs->trans("LDAPDescValues"));

/*
 * Test de la connexion
 */
if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
{
	$butlabel=$langs->trans("LDAPTestSynchroGroup");
	$testlabel='testgroup';
	$key=$conf->global->LDAP_KEY_GROUPS;
	$dn=$conf->global->LDAP_GROUP_DN;
	$objectclass=$conf->global->LDAP_GROUP_OBJECT_CLASS;

	show_ldap_test_button($butlabel,$testlabel,$key,$dn,$objectclass);
}

if (function_exists("ldap_connect"))
{
	if ($_GET["action"] == 'testgroup')
	{
		// Creation objet
		$object=new UserGroup($db);
		$object->initAsSpecimen();

		// Test synchro
		$ldap=new Ldap();
		$result=$ldap->connect_bind();

		if ($result > 0)
		{
			$info=$object->_load_ldap_info();
			$dn=$object->_load_ldap_dn($info);

			$result1=$ldap->delete($dn);			// To be sure to delete existing records
			$result2=$ldap->add($dn,$info,$user);	// Now the test
			$result3=$ldap->delete($dn);			// Clean what we did

			if ($result2 > 0)
			{
				print img_picto('','info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
			}
			else
			{
				print img_picto('','error').' ';
				print '<font class="error">'.$langs->trans("LDAPSynchroKOMayBePermissions");
				print ': '.$ldap->error;
				print '</font><br>';
				print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
			}

			print "<br>\n";
			print "LDAP input file used for test:<br><br>\n";
			print nl2br($ldap->dump_content($dn,$info));
			print "\n<br>";
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
			print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
		}
	}
}

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
