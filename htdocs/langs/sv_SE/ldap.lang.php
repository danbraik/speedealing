<?php
/* Copyright (C) 2012	Regis Houssin	<regis.houssin@capnetworks.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$ldap = array(
		'CHARSET' => 'UTF-8',
		'DomainPassword' => 'Lösenord för domän',
		'YouMustChangePassNextLogon' => 'Lösenord för användare <b>%s</b> på domänen <b>%s</b> måste ändras.',
		'UserMustChangePassNextLogon' => 'Användaren måste byta lösenord på domänen %s',
		'LdapUacf_NORMAL_ACCOUNT' => 'Användarkonto',
		'LdapUacf_DONT_EXPIRE_PASSWORD' => 'Lösenordet upphör aldrig att',
		'LdapUacf_ACCOUNTDISABLE' => 'Kontot är inaktivt i domänen %s',
		'LDAPInformationsForThisContact' => 'Information i LDAP-databas för denna kontakt',
		'LDAPInformationsForThisUser' => 'Information i LDAP-databas för denna användare',
		'LDAPInformationsForThisGroup' => 'Information i LDAP-databas för denna grupp',
		'LDAPInformationsForThisMember' => 'Information i LDAP-databas för denna medlem',
		'LDAPAttribute' => 'LDAP-attribut',
		'LDAPAttributes' => 'LDAP-attribut',
		'LDAPCard' => 'LDAP-kort',
		'LDAPRecordNotFound' => 'Spela som inte finns i LDAP-databas',
		'LDAPUsers' => 'Användare i LDAP-databas',
		'LDAPGroups' => 'Grupper i LDAP-databas',
		'LDAPFieldStatus' => 'Status',
		'LDAPFieldFirstSubscriptionDate' => 'Första teckningsdag',
		'LDAPFieldFirstSubscriptionAmount' => 'Fist teckningsbelopp',
		'LDAPFieldLastSubscriptionDate' => 'Sista teckningsdag',
		'LDAPFieldLastSubscriptionAmount' => 'Senaste teckningsbelopp',
		'SynchronizeDolibarr2Ldap' => 'Synkronisera användare (Dolibarr -> LDAP)',
		'UserSynchronized' => 'Användare synkroniseras',
		'GroupSynchronized' => 'Grupp synkroniseras',
		'MemberSynchronized' => 'Medlem synkroniseras',
		'ContactSynchronized' => 'Kontakta synkroniseras',
		'ForceSynchronize' => 'Force synkronisera Dolibarr -> LDAP',
		'ErrorFailedToReadLDAP' => 'Misslyckades med att läsa LDAP-databas. Kontrollera LDAP-modul setup och databas tillgänglighet.'
);
?>