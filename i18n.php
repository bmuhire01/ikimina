<?php
// i18n.php - English and Kinyarwanda translations.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Switch language with ?lang=en or ?lang=rw. The choice is remembered.
if (isset($_GET['lang'])) {
    $requested = $_GET['lang'] === 'rw' ? 'rw' : ($_GET['lang'] === 'en' ? 'en' : '');
    if ($requested !== '') {
        $_SESSION['lang'] = $requested;
        setcookie('ikimina_lang', $requested, time() + 31536000, '/');
    }
}

// Session first, then the cookie, then the browser preference.
$lang = 'en';
if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], array('en', 'rw'), true)) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['ikimina_lang']) && in_array($_COOKIE['ikimina_lang'], array('en', 'rw'), true)) {
    $lang = $_COOKIE['ikimina_lang'];
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'rw') !== false) {
    $lang = 'rw';
}

$GLOBALS['APP_LANG'] = $lang;

function current_lang()
{
    return $GLOBALS['APP_LANG'];
}

function other_lang()
{
    return current_lang() === 'rw' ? 'en' : 'rw';
}

// Link to the same page in another language, keeping the query string.
function language_url($lang)
{
    $query = $_GET;
    $query['lang'] = $lang;
    return basename($_SERVER['PHP_SELF']) . '?' . http_build_query($query);
}

function translations()
{
    static $rw = null;

    if ($rw === null) {
        $rw = array(
            // Navigation and common words
            'Home' => 'Ahabanza',
            'Members' => 'Abanyamuryango',
            'Member' => 'Umunyamuryango',
            'Money' => 'Amafaranga',
            'Loans' => 'Inguzanyo',
            'More' => 'Ibindi',
            'Login' => 'Injira',
            'Logout' => 'Sohoka',
            'Log out' => 'Sohoka',
            'Password' => 'Ijambo ry\'ibanga',
            'Change password' => 'Hindura ijambo ry\'ibanga',
            'Username' => 'Izina ry\'ukoresha',
            'Current password' => 'Ijambo ry\'ibanga risanzwe',
            'New password' => 'Ijambo ry\'ibanga rishya',
            'Confirm password' => 'Emeza ijambo ry\'ibanga',
            'Confirm new password' => 'Emeza ijambo ry\'ibanga rishya',
            'Secretary' => 'Umunyamabanga',
            'Group administrator' => 'Umuyobozi w\'itsinda',
            'Account' => 'Konti',
            'Language' => 'Ururimi',
            'Switch language' => 'Hindura ururimi',
            'English' => 'Icyongereza',
            'Kinyarwanda' => 'Ikinyarwanda',
            'Account and more' => 'Konti n\'ibindi',
            'Your account and group tools' => 'Konti yawe n\'ibikoresho by\'itsinda',
            'Group' => 'Itsinda',
            'Group information' => 'Amakuru y\'itsinda',
            'Group settings' => 'Igenamiterere ry\'itsinda',
            'Settings' => 'Igenamiterere',
            'Default loan interest' => 'Inyungu isanzwe y\'inguzanyo',
            'Monthly contribution' => 'Umusanzu w\'ukwezi',
            'Not set' => 'Ntibyashyizweho',
            'Save' => 'Bika',
            'Save changes' => 'Bika impinduka',
            'Cancel' => 'Reka',
            'Back' => 'Subira',
            'Search' => 'Shakisha',
            'Clear' => 'Siba',
            'View' => 'Reba',
            'Add' => 'Ongeraho',
            'Edit' => 'Hindura',
            'Record' => 'Andika',
            'See all' => 'Reba byose',
            'Clear search' => 'Siba ibyashakishijwe',
            'This month' => 'Ukwezi kuri',
            'Upcoming' => 'Bitezweho',
            'Past' => 'Byashize',
            'Active' => 'Arakora',
            'Inactive' => 'Ntakora',
            'Paid' => 'Yishyuwe',
            'Unpaid' => 'Ntiyishyuwe',
            'Overdue' => 'Yarenze igihe',
            'Open' => 'Bifunguye',
            'Reason' => 'Impamvu',
            'Place' => 'Ahantu',
            'Notes' => 'Ibisobanuro',
            'Interest' => 'Inyungu',
            'Amount' => 'Amafaranga',
            'Date' => 'Itariki',
            'Total' => 'Igiteranyo',
            'Outstanding' => 'Bisigaye',
            'Repaid' => 'Byishyuwe',
            'RWF' => 'RWF',

            // Login
            'Wrong username or password.' => 'Izina ry\'ukoresha cyangwa ijambo ry\'ibanga sibyo.',
            'Your account is inactive. Please contact the secretary.' => 'Konti yawe ntikora. Vugana n\'umunyamabanga.',
            'Contact the secretary if you need an account.' => 'Vugana n\'umunyamabanga niba ukeneye konti.',
            'Login - %s' => 'Injira - %s',

            // Dashboard
            'Hello, %s' => 'Muraho, %s',
            'Your total savings' => 'Amafaranga yawe yose yabitswe',
            'Group savings' => 'Amafaranga y\'itsinda yabitswe',
            'Collected this month' => 'Byakusanyijwe ukwezi kuri',
            '%d active member' => '%d umunyamuryango urakora',
            '%d active members' => '%d abanyamuryango bakora',
            'Loans outstanding (RWF)' => 'Inguzanyo zisigaye (RWF)',
            'Unpaid penalties (RWF)' => 'Ibihano bitishyuwe (RWF)',
            'Open loans' => 'Inguzanyo zifunguye',
            'Members in total' => 'Abanyamuryango bose',
            'Next meeting' => 'Inama ikurikira',
            'All meetings' => 'Inama zose',
            'No meeting has been scheduled yet.' => 'Nta nama yagenwe cyane.',
            'Schedule a meeting' => 'Gena inama',
            'Recent contributions' => 'Imisanzu ya vuba',
            'Your recent payments' => 'Ubwishyu bwawe bwa vuba',
            'No contributions recorded yet.' => 'Nta misanzu yanditswe cyane.',
            'Record one' => 'Andika umwe',
            'Loan interest' => 'Inyungu y\'inguzanyo',
            'Edit settings' => 'Hindura igenamiterere',
            'Contribution' => 'Umusanzu',
            'Balances' => 'Amafaranga yabitswe',
            'Payments' => 'Ubwishyu',
            'Meetings' => 'Inama',
            'New loan' => 'Inguzanyo nshya',
            'Meeting' => 'Inama',

            // Members
            'Search by name or phone' => 'Shakisha ku izina cyangwa telefoni',
            'e.g. Jackson or 0790' => 'urugero: Jackson cyangwa 0790',
            '%d member in the group' => '%d umunyamuryango uri mu itsinda',
            '%d members in the group' => '%d abanyamuryango bari mu itsinda',
            '%d member found' => '%d umunyamuryango wabonetse',
            '%d members found' => '%d abanyamuryango babonetse',
            '%d active' => '%d bakora',
            'No members yet.' => 'Nta banyamuryango barabaho.',
            'No members match "%s".' => 'Nta munyamuryango uhuye na "%s".',
            'Add the first member' => 'Ongeraho umunyamuryango wa mbere',
            '+ Add' => '+ Ongeraho',
            'Joined' => 'Yinjiye',
            'joined %s' => 'yinjiye %s',
            'No login account' => 'Nta konti yo kwinjira',
            'Create login' => 'Kora konti yo kwinjira',
            'Manage login' => 'Gucunga kwinjira',
            'Login:' => 'Kwinjira:',
            'Full name' => 'Amazina yombi',
            'Phone number' => 'Numero ya telefoni',
            'Phone' => 'Telefoni',
            'Join date' => 'Itariki yinjiye',
            'Status' => 'Imiterere',
            'Inactive members cannot log in.' => 'Abanyamuryango badakora ntibashobora kwinjira.',
            'Add member' => 'Ongeraho umunyamuryango',
            'Only the secretary can add members.' => 'Umunyamabanga gusa ni we ushobora kongeraho abanyamuryango.',
            'Example: 0788123456' => 'Urugero: 0788123456',
            'Save member' => 'Bika umunyamuryango',
            'After saving, use the Create login button on the members list to give this member a view-only account.' => 'Nyuma yo kubika, koresha buto yo gukora konti ku rutonde rw\'abanyamuryango kugira ngo uhe uyu munyamuryango konti yo kureba gusa.',
            'Member "%s" was added.' => 'Umunyamuryango "%s" yongeweho.',
            'Edit member' => 'Hindura umunyamuryango',
            'Update the details for %s.' => 'Hindura amakuru ya %s.',
            'Member "%s" was updated.' => 'Umunyamuryango "%s" yahinduwe.',
            'Member not found.' => 'Umunyamuryango ntabonetse.',
            'Back to members' => 'Subira ku banyamuryango',
            'Member login' => 'Kwinjira kw\'umunyamuryango',
            'This member does not have a login yet.' => 'Uyu munyamuryango nta konti yo kwinjira afite.',
            'this member must be active before the login will work.' => 'uyu munyamuryango agomba kuba arakora mbere y\'uko yinjira.',
            'For %s' => 'Kuri %s',
            'This member is inactive. The login will not work until the member is set back to active.' => 'Uyu munyamuryango ntakora. Kwinjira ntibizakora kugeza uyu munyamuryango asubijwe mu bakora.',
            '3-50 letters, numbers or underscores.' => 'Inyuguti 3-50, imibare cyangwa _.',
            'Leave both password fields blank to keep the current password.' => 'Siga ibi bice byombi by\'ijambo ry\'ibanga ubusa kugira ngo ugumane iriho.',
            'At least 6 characters.' => 'Nibura inyuguti 6.',
            'This account will have the member role and can only view records.' => 'Iyi konti izaba ifite uruhare rw\'umunyamuryango kandi ishobora kureba gusa.',
            'Save login' => 'Bika kwinjira',
            'Login created for %s.' => 'Konti yo kwinjira yakiwe kuri %s.',
            'Login updated for %s.' => 'Konti yo kwinjira yavuguruwe kuri %s.',
            'A password is required for a new login.' => 'Ijambo ry\'ibanga rirakenewe kuri konti nshya.',
            'Password must be at least 6 characters.' => 'Ijambo ry\'ibanga rigomba kuba nibura inyuguti 6.',
            'The two passwords do not match.' => 'Amagambo y\'ibanga abiri ntabwo ahura.',
            'Please enter the new password in both fields.' => 'Andika ijambo ry\'ibanga rishya mu bice byombi.',
            'The two new passwords do not match.' => 'Amagambo y\'ibanga mashya abiri ntabwo ahura.',
            'Username is required.' => 'Izina ry\'ukoresha rirakenewe.',
            'Username must be 3-50 letters, numbers or underscores.' => 'Izina ry\'ukoresha rigomba kuba inyuguti 3-50, imibare cyangwa _. ',
            'That username is already used by another account.' => 'Iryo zina ry\'ukoresha risanzwe rikoreshwa na konti indi.',
            'Back to dashboard' => 'Subira ahabanza',

            // Registration, families and family members
            'Register' => 'Andikisha',
            'Choose how to register' => 'Hitamo uburyo bwo kwandikisha',
            'Individual person' => 'Umuntu ku giti cye',
            'Register one person with their own phone number.' => 'Andikisha umuntu umwe ufite numero ye ya telefoni.',
            'Register a family, then add the members who save together.' => 'Andikisha umuryango, hanyuma wongereho abanyamuryango bizigama hamwe.',
            'An individual person saves alone. A family groups a parent or guardian and the people who save together.' => 'Umuntu ku giti cye yizigama wenyine. Umuryango uhuza umubyeyi cyangwa umurera n\'abantu bizigama hamwe.',
            'Register the first member' => 'Andikisha umunyamuryango wa mbere',
            'Families' => 'Imiryango',
            'Family' => 'Umuryango',
            'Children' => 'Abana',
            'Child' => 'Umwana',
            'Adult' => 'Umuntu mukuru',
            'Family members' => 'Abanyamuryango b\'umuryango',
            'Family member' => 'Umunyamuryango w\'umuryango',
            '%d family member' => '%d umunyamuryango w\'umuryango',
            '%d family members' => '%d abanyamuryango b\'umuryango',
            'Add family member' => 'Ongeraho umunyamuryango w\'umuryango',
            '+ Add member' => '+ Ongeraho umunyamuryango',
            'Add the first family member' => 'Ongeraho umunyamuryango w\'umuryango wa mbere',
            'Edit family member' => 'Hindura umunyamuryango w\'umuryango',
            'Save family member' => 'Bika umunyamuryango w\'umuryango',
            'Member type' => 'Ubwoko bw\'umunyamuryango',
            'Please choose a valid member type.' => 'Hitamo ubwoko bw\'umunyamuryango bwemewe.',
            'Phone number is required for an adult.' => 'Numero ya telefoni irakenewe ku muntu mukuru.',
            'Adults need a phone number. It is optional for children.' => 'Abantu bakuru bakeneye numero ya telefoni. Ku bana ntibitegetswe.',
            'No members in this family yet.' => 'Nta banyamuryango bari mu muryango ubu.',
            'Add the members who save with this family.' => 'Ongeraho abanyamuryango bizigama n\'uyu muryango.',
            'The family member will appear in the members list and you can record savings for them.' => 'Umunyamuryango w\'umuryango azagaragara ku rutonde rw\'abanyamuryango kandi ushobora kwandika ubwizigame bwe.',
            'Family members are grouped under their family.' => 'Abanyamuryango b\'umuryango bashyizwe mu miryango yabo.',
            '-- Choose a family --' => '-- Hitamo umuryango --',
            'Choose the family first, then the member who paid.' => 'Banza uhitemo umuryango, hanyuma uhitemo umunyamuryango wishyuye.',
            'Please choose a family or individual member.' => 'Hitamo umuryango cyangwa umunyamuryango ku giti cye.',
            'The selected member does not belong to the chosen family.' => 'Umunyamuryango wahiswemo ntabwo ari mu muryango wahiswemo.',
            'Search by family, guardian or member' => 'Shakisha ku muryango, umubyeyi cyangwa umunyamuryango',
            'Register a family, then add their family members.' => 'Andikisha umuryango, hanyuma wongereho abanyamuryango bawo.',
            'After saving, add the family members who save together.' => 'Nyuma yo kubika, ongeraho abanyamuryango bizigama hamwe.',
            'Only the secretary can add family members.' => 'Umunyamabanga gusa ni we ushobora kongeraho abanyamuryango b\'umuryango.',
            'Family member "%s" was added to %s.' => 'Umunyamuryango w\'umuryango "%s" yongewe mu %s.',
            'Family member "%s" was updated.' => 'Umunyamuryango w\'umuryango "%s" yahinduwe.',
            'Could not save the family member: %s' => 'Ntibyashobotse kubika umunyamuryango w\'umuryango: %s',
            'Could not update the family member: %s' => 'Ntibyashobotse kuvugurura umunyamuryango w\'umuryango: %s',
            'Family member not found.' => 'Umunyamuryango w\'umuryango ntabonetse.',
            'Register families and their members' => 'Andikisha imiryango n\'abanyamuryango bayo',

            // Group report and overview
            'Group report' => 'Raporo y\'itsinda',
            'An overview of the whole savings group.' => 'Incamake y\'itsinda ryose ry\'ubwizigame.',
            'Overview of the whole group\'s savings' => 'Incamake y\'ubwizigame bw\'itsinda ryose',
            'Open report' => 'Fungura raporo',
            'Your savings' => 'Ubwizigame bwawe',
            'Children in families' => 'Abana bari mu miryango',
            'Collected in %s' => 'Byakusanyijwe muri %s',
            'Collection rate' => 'Igipimo cyo kwishyura',
            'Savings trend' => 'Imigendekere y\'ubwizigame',
            'Last 6 months' => 'Amezi 6 ashize',
            'Top savers' => 'Abizigama cyane',
            'Individual member' => 'Umunyamuryango ku giti cye',
            'Loans and penalties' => 'Inguzanyo n\'ibihano',
            '%d unpaid penalty' => '%d igihano kitishyuwe',
            '%d unpaid penalties' => '%d ibihano bitishyuwe',
            'Active members' => 'Abanyamuryango bakora',
            'Family savings' => 'Ubwizigame bw\'umuryango',
            'Children\'s savings' => 'Ubwizigame bw\'abana',
            'Children in savings' => 'Abana mu bwizigame',
            'Registered families' => 'Imiryango yanditswe',
            'Register family' => 'Andikisha umuryango',
            'Register the first family' => 'Andikisha umuryango wa mbere',
            'Register a family, then add their children.' => 'Andikisha umuryango, hanyuma wongereho abana babo.',
            'Register families and save for their children' => 'Andikisha imiryango kandi ubikire abana babo',
            'Edit family' => 'Hindura umuryango',
            'Back to families' => 'Subira ku miryango',
            'Back to family' => 'Subira ku muryango',
            'Save family' => 'Bika umuryango',
            'Family name' => 'Izina ry\'umuryango',
            'Family information' => 'Amakuru y\'umuryango',
            'Parent or guardian' => 'Umubyeyi cyangwa umurera',
            'Parent or guardian phone (optional)' => 'Telefoni y\'umubyeyi cyangwa umurera (ntibitegetswe)',
            'Phone number is optional.' => 'Numero ya telefoni ntitegetswe.',
            'Registration date' => 'Itariki yo kwandikisha',
            'Example: Uwimana family' => 'Urugero: Umuryango wa Uwimana',
            'After saving, add the children who save with this family.' => 'Nyuma yo kubika, ongeraho abana bizigama n\'uyu muryango.',
            'Add child' => 'Ongeraho umwana',
            '+ Add child' => '+ Ongeraho umwana',
            'Add the first child' => 'Ongeraho umwana wa mbere',
            'Edit child' => 'Hindura umwana',
            'Save child' => 'Bika umwana',
            'Record saving' => 'Andika ubwizigame',
            'No children in this family yet.' => 'Nta bana bari mu muryango ubu.',
            'Add the children who save with this family.' => 'Ongeraho abana bizigama n\'uyu muryango.',
            'Date of birth' => 'Itariki y\'amavuko',
            'Date of birth (optional)' => 'Itariki y\'amavuko (ntibitegetswe)',
            'No date of birth' => 'Nta tariki y\'amavuko',
            '%d years old' => '%d imyaka',
            'No phone' => 'Nta telefoni',
            'View family' => 'Reba umuryango',
            'Search by family, guardian or child' => 'Shakisha ku muryango, umubyeyi cyangwa umwana',
            'e.g. Uwimana or 0790' => 'urugero: Uwimana cyangwa 0790',
            'No families yet.' => 'Nta miryango irabaho.',
            'No families match "%s".' => 'Nta muryango uhuye na "%s".',
            '%d family' => '%d umuryango',
            '%d families' => '%d imiryango',
            '%d family found' => '%d umuryango wabonetse',
            '%d families found' => '%d imiryango yabonetse',
            '%d child' => '%d umwana',
            '%d children' => '%d abana',
            '%d child in the family' => '%d umwana mu muryango',
            '%d children in the family' => '%d abana mu muryango',
            'Family not found.' => 'Umuryango ntabonetse.',
            'Child not found.' => 'Umwana ntabonetse.',
            'Family name is required.' => 'Izina ry\'umuryango rirakenewe.',
            'Family name must be 100 characters or fewer.' => 'Izina ry\'umuryango rigomba kuba inyuguti 100 cyangwa nkeya.',
            'Parent or guardian name is required.' => 'Izina ry\'umubyeyi cyangwa umurera rirakenewe.',
            'Parent or guardian name must be 100 characters or fewer.' => 'Izina ry\'umubyeyi cyangwa umurera rigomba kuba inyuguti 100 cyangwa nkeya.',
            'Please choose a valid registration date.' => 'Hitamo itariki yo kwandikisha yemewe.',
            'Please choose a valid date of birth.' => 'Hitamo itariki y\'amavuko yemewe.',
            'Date of birth cannot be in the future.' => 'Itariki y\'amavuko ntishobora kuba mu gihe kizaza.',
            'Family "%s" was registered.' => 'Umuryango "%s" wanditswe.',
            'Family "%s" was updated.' => 'Umuryango "%s" wahinduwe.',
            'Child "%s" was added to %s.' => 'Umwana "%s" yongewe mu %s.',
            'Child "%s" was updated.' => 'Umwana "%s" yahinduwe.',
            'Could not save the family: %s' => 'Ntibyashobotse kubika umuryango: %s',
            'Could not update the family: %s' => 'Ntibyashobotse kuvugurura umuryango: %s',
            'Could not save the child: %s' => 'Ntibyashobotse kubika umwana: %s',
            'Could not update the child: %s' => 'Ntibyashobotse kuvugurura umwana: %s',
            'Only the secretary can register families.' => 'Umunyamabanga gusa ni we ushobora kwandikisha imiryango.',
            'The child will appear in the members list and you can record savings for them.' => 'Umwana azagaragara ku rutonde rw\'abanyamuryango kandi ushobora kwandika ubwizigame bwe.',
            'To save for a child, register a family and add the child there.' => 'Kugira ngo ubikire umwana, andikisha umuryango hanyuma wongereho umwana.',
            'Children do not have login accounts.' => 'Abana ntibagira konti zo kwinjira.',
            'Use the member form to edit this person.' => 'Koresha ifishi y\'umunyamuryango kugira ngo uhindure uyu muntu.',
            'Children are grouped under their family.' => 'Abana bashyizwe mu miryango yabo.',

            // Contributions
            'Contributions' => 'Imisanzu',
            'Payments received in %s.' => 'Ubwishyu bwakiriwe muri %s.',
            'Choose month' => 'Hitamo ukwezi',
            'View month' => 'Reba ukwezi',
            'RWF collected' => 'RWF byakusanyijwe',
            'Members who paid' => 'Abanyamuryango bishyuye',
            'Not paid yet' => 'Ntibishyuye',
            'Average per payer (RWF)' => 'Impuzandengo ku wishyuye (RWF)',
            'Payments received' => 'Ubwishyu bwakiriwe',
            'No contributions recorded for %s.' => 'Nta misanzu yanditswe muri %s.',
            'Record the first payment' => 'Andika ubwishyu bwa mbere',
            'Everyone has paid for %s.' => 'Bose bishyuye muri %s.',
            'No members had joined by %s.' => 'Nta banyamuryango bari barinjiye muri %s.',
            'Record contribution' => 'Andika umusanzu',
            'Only the secretary can record payments.' => 'Umunyamabanga gusa ni we ushobora kwandika ubwishyu.',
            'You need at least one member before recording a contribution.' => 'Ukeneye nibura umunyamuryango umwe mbere yo kwandika umusanzu.',
            '-- Choose a member --' => '-- Hitamo umunyamuryango --',
            'Amount (RWF)' => 'Amafaranga (RWF)',
            'Whole francs only, for example 5000.' => 'Amafaranga yuzuye gusa, urugero 5000.',
            'Payment date' => 'Itariki y\'ubwishyu',
            'Save contribution' => 'Bika umusanzu',
            'Back to contributions' => 'Subira ku misanzu',
            'Recorded %s RWF for %s.' => 'Byanditswe %s RWF kuri %s.',
            'Please choose a valid amount.' => 'Hitamo amafaranga yemewe.',
            'Amount must be more than zero.' => 'Amafaranga agomba kurenza zero.',
            'Amount must be a whole number of RWF.' => 'Amafaranga agomba kuba umubare wuzuye wa RWF.',
            'Amount is too large.' => 'Amafaranga ni menshi cyane.',
            'Please choose a valid payment date.' => 'Hitamo itariki y\'ubwishyu yemewe.',
            'Payment date cannot be in the future.' => 'Itariki y\'ubwishyu ntishobora kuba mu gihe kizaza.',

            // Balances
            'Total saved by each member' => 'Amafaranga yose yabitswe n\'umunyamuryango',
            'Total saved' => 'Igiteranyo cyabitswe',
            'Read-only view: you can see the group records but cannot change them.' => 'Kureba gusa: ushobora kureba inyandiko z\'itsinda ariko ntushobora kuzihindura.',
            'Group total saved' => 'Igiteranyo cy\'itsinda cyabitswe',
            '%d member' => '%d umunyamuryango',
            '%d members' => '%d abanyamuryango',
            'payment' => 'ubwishyu',
            'payments' => 'ubwishyu',
            'last %s' => 'bwa nyuma %s',
            'no payments yet' => 'nta bwishyu burabaho',
            'No members yet.' => 'Nta banyamuryango barabaho.',

            // Loans
            '%d loan' => '%d inguzanyo',
            '%d loans' => '%d inguzanyo',
            '%d still open' => '%d ziracyafunguye',
            'Still to be repaid' => 'Bisigaye kwishyurwa',
            'Principal issued' => 'Amafaranga yatanzwe',
            'Loans in total' => 'Inguzanyo zose',
            'Repaid (RWF)' => 'Byishyuwe (RWF)',
            'All loans' => 'Inguzanyo zose',
            'No loans have been given yet.' => 'Nta nguzanyo yatanzwe cyane.',
            'Give the first loan' => 'Tanga inguzanyo ya mbere',
            'Issued %s' => 'Yatanzwe %s',
            'due %s' => 'ishyura %s',
            'Paid %s of %s RWF' => 'Byishyuwe %s kuri %s RWF',
            'Record repayment' => 'Andika ubwishyu',
            'outstanding' => 'bisigaye',
            'Only the secretary can give a loan' => 'Umunyamabanga gusa ni we ushobora gutanga inguzanyo',
            'Back' => 'Subira',
            'You need an active member before giving a loan.' => 'Ukeneye umunyamuryango urakora mbere yo gutanga inguzanyo.',
            'Loan amount (RWF)' => 'Inguzanyo (RWF)',
            'Whole francs only, for example 50000.' => 'Amafaranga yuzuye gusa, urugero 50000.',
            'Interest (%)' => 'Inyungu (%)',
            'The total to repay is the amount plus this interest.' => 'Igiteranyo cyo kwishyura ni amafaranga yatanzwe hiyongereyeho inyungu.',
            'Loan date' => 'Itariki y\'inguzanyo',
            'Due date' => 'Itariki yo kwishyura',
            'Save loan' => 'Bika inguzanyo',
            'Back to loans' => 'Subira ku nguzanyo',
            'Loan of %s RWF recorded for %s. Total to repay: %s RWF.' => 'Inguzanyo ya %s RWF yanditswe kuri %s. Igiteranyo cyo kwishyura: %s RWF.',
            'Money paid back by %s' => 'Amafaranga yishyuwe na %s',
            'Loan amount' => 'Inguzanyo',
            'Total to repay' => 'Igiteranyo cyo kwishyura',
            'Already paid' => 'Byishyuwe',
            'This loan has been repaid in full.' => 'Iyi nguzanyo yishyuwe yose.',
            'Amount paid (RWF)' => 'Amafaranga yishyuwe (RWF)',
            'Up to %s RWF.' => 'Kugeza kuri %s RWF.',
            'Save repayment' => 'Bika ubwishyu',
            'Recorded %s RWF repayment from %s.' => 'Byanditswe ubwishyu bwa %s RWF kuri %s.',
            'Repayment cannot be more than the outstanding %s RWF.' => 'Ubwishyu ntibushobora kurenza bisigaye %s RWF.',
            'This loan has already been repaid in full.' => 'Iyi nguzanyo yamaze kwishyurwa yose.',
            'Please enter a valid repayment amount.' => 'Andika amafaranga y\'ubwishyu yemewe.',
            'Repayment must be more than zero.' => 'Ubwishyu bugomba kurenza zero.',
            'Repayment must be a whole number of RWF.' => 'Ubwishyu bugomba kuba umubare wuzuye wa RWF.',
            'Please choose a valid loan amount.' => 'Andika inguzanyo yemewe.',
            'Loan amount must be more than zero.' => 'Inguzanyo igomba kurenza zero.',
            'Loan amount must be a whole number of RWF.' => 'Inguzanyo igomba kuba umubare wuzuye wa RWF.',
            'Loan amount is too large.' => 'Inguzanyo ni nyinshi cyane.',
            'Please enter a valid interest rate.' => 'Andika inyungu yemewe.',
            'Interest must be between 0 and 100 percent.' => 'Inyungu igomba kuba hagati ya 0 na 100 ku ijana.',
            'Please choose a valid loan date.' => 'Hitamo itariki y\'inguzanyo yemewe.',
            'Please choose a valid due date.' => 'Hitamo itariki yo kwishyura yemewe.',
            'The due date cannot be before the loan date.' => 'Itariki yo kwishyura ntishobora kuba mbere y\'itariki y\'inguzanyo.',
            'Loan not found.' => 'Inguzanyo ntabonetse.',

            // Penalties
            'Penalties' => 'Ibihano',
            'Unpaid penalties' => 'Ibihano bitishyuwe',
            'still to pay' => 'biracyishyurwa',
            'Collected' => 'Byakusanyijwe',
            'All penalties' => 'Ibihano byose',
            'No penalties have been charged.' => 'Nta bihano byatanzwe.',
            'Add a penalty' => 'Ongeraho igihano',
            'Mark paid' => 'Menyekanisha ko yishyuwe',
            'Mark unpaid' => 'Menyekanisha ko itishyuwe',
            'Penalty marked as paid.' => 'Igihano cyamenyekanishijwe ko cyishyuwe.',
            'Penalty marked as unpaid.' => 'Igihano cyamenyekanishijwe ko kitishyuwe.',
            'Add penalty' => 'Ongeraho igihano',
            'Only the secretary can charge a fine' => 'Umunyamabanga gusa ni we ushobora gutanga igihano',
            'You need at least one member before adding a penalty.' => 'Ukeneye nibura umunyamuryango umwe mbere yo kongeraho igihano.',
            'e.g. Late for the meeting' => 'urugero: Yakererewe mu nama',
            'Save penalty' => 'Bika igihano',
            'Back to penalties' => 'Subira ku bihano',
            'Please write the reason for the penalty.' => 'Andika impamvu y\'igihano.',
            'The reason must be 255 characters or fewer.' => 'Impamvu igomba kuba inyuguti 255 cyangwa nkeya.',
            'Penalty of %s RWF added for %s.' => 'Igihano cya %s RWF cyongeweho kuri %s.',
            'Please choose a valid penalty date.' => 'Hitamo itariki y\'igihano yemewe.',
            'Penalty date cannot be in the future.' => 'Itariki y\'igihano ntishobora kuba mu gihe kizaza.',

            // Meetings and attendance
            'Meetings' => 'Inama',
            '%d meeting' => '%d inama',
            '%d meetings' => '%d inama',
            '%d upcoming' => '%d zitezweho',
            'No meetings have been scheduled yet.' => 'Nta nama zagenwe cyane.',
            'Schedule the first meeting' => 'Gena inama ya mbere',
            '%d of %d members present' => '%d kuri %d abanyamuryango bahari',
            'Mark attendance' => 'Andika abahari',
            'View attendance' => 'Reba abahari',
            'New meeting' => 'Inama nshya',
            'Only the secretary can schedule meetings' => 'Umunyamabanga gusa ni we ushobora kugena inama',
            'Meeting date' => 'Itariki y\'inama',
            'e.g. Village hall' => 'urugero: Icyumba cy\'inama',
            'Notes (optional)' => 'Ibisobanuro (bidategetswe)',
            'What will the group discuss?' => 'Ni iki itsinda rizaganira?',
            'Save meeting' => 'Bika inama',
            'Meeting created. Now mark who attended.' => 'Inama yagenwe. Andika abayitabiriye.',
            'Attendance' => 'Abahari',
            '%d of %d present' => '%d kuri %d bahari',
            'Select all' => 'Hitamo bose',
            'Clear all' => 'Siba bose',
            'There are no members to mark yet.' => 'Nta banyamuryango bafite gushyirwaho.',
            'Save attendance' => 'Bika abahari',
            'Attendance saved.' => 'Abahari banditswe.',
            'Meeting not found.' => 'Inama ntabonetse.',
            'Back to meetings' => 'Subira ku nama',
            'Please choose a valid meeting date.' => 'Hitamo itariki y\'inama yemewe.',
            'Please write where the meeting will be held.' => 'Andika aho inama izabera.',
            'The place must be 150 characters or fewer.' => 'Ahantu hagomba kuba inyuguti 150 cyangwa nkeya.',

            // Settings
            'Group name' => 'Izina ry\'itsinda',
            'Group name is required.' => 'Izina ry\'itsinda rirakenewe.',
            'Group name must be 150 characters or fewer.' => 'Izina ry\'itsinda rigomba kuba inyuguti 150 cyangwa nkeya.',
            'Monthly contribution (RWF)' => 'Umusanzu w\'ukwezi (RWF)',
            'Use 0 if the group has not fixed an amount yet.' => 'Koresha 0 niba itsinda ritari ryemeje amafaranga.',
            'Default loan interest (%)' => 'Inyungu isanzwe y\'inguzanyo (%)',
            'Used as the default when a new loan is created.' => 'Ikoreshwa nk\'isanzwe iyo inguzanyo nshya ikozwe.',
            'Save settings' => 'Bika igenamiterere',
            'Group settings saved.' => 'Igenamiterere ry\'itsinda ryabitswe.',
            'Only the secretary can change these' => 'Umunyamabanga gusa ni we ushobora guhindura ibi',
            'Please enter a valid monthly contribution.' => 'Andika umusanzu w\'ukwezi wemewe.',
            'Monthly contribution cannot be negative.' => 'Umusanzu w\'ukwezi ntushobora kuba mubi.',
            'Monthly contribution must be a whole number of RWF.' => 'Umusanzu w\'ukwezi ugomba kuba umubare wuzuye wa RWF.',
            'Monthly contribution is too large.' => 'Umusanzu w\'ukwezi ni munini cyane.',

            // More page
            'Your account and group tools' => 'Konti yawe n\'ibikoresho by\'itsinda',
            'How much each member has saved' => 'Amafaranga yabitswe n\'umunyamuryango',
            'Monthly payments and who has not paid' => 'Imisanzu y\'ukwezi n\'abatarayishyura',
            'Money lent and repayments' => 'Inguzanyo n\'ubwishyu',
            'Fines charged to members' => 'Ibihano byahawe abanyamuryango',
            'People in the savings group' => 'Abantu bari mu itsinda ry\'ubwizigame',
            'Schedule and attendance' => 'Gena no kwandika abahari',
            'Group name, contribution and interest' => 'Izina ry\'itsinda, umusanzu n\'inyungu',
            'Keep your account safe' => 'Komeza konti yawe mu mutekano',
            'End this session' => 'Sohoka ubu',
            'Add it to your home screen from the browser menu.' => 'Yishyire ku ndangantoki yawe ukoresheje menu ya mushakisha.',
            'Ikimina works best on a phone. Add it to your home screen from the browser menu.' => 'Ikimina gikora neza kuri telefoni. Yishyire ku ndangantoki yawe ukoresheje menu ya mushakisha.',

            // Change password
            'Signed in as %s' => 'Winjiye nka %s',
            'This account still uses the default password admin123. Choose a new password now so nobody else can sign in as you.' => 'Iyi konti iracyakoresha ijambo ry\'ibanga risanzwe admin123. Hitamo ijambo ry\'ibanga rishya nonaha kugira ngo nta wundi winjire nkawe.',
            'Your password was changed.' => 'Ijambo ry\'ibanga ryahinduwe.',
            'Could not save the new password: %s' => 'Ntibyashobotse kubika ijambo ry\'ibanga rishya: %s',
            'Your current password is not correct.' => 'Ijambo ry\'ibanga risanzwe si ryo.',
            'Your account could not be found. Please log in again.' => 'Konti yawe ntiyabonetse. Ongera winjire.',
            'Please enter your current password.' => 'Andika ijambo ry\'ibanga risanzwe.',
            'Please enter a new password.' => 'Andika ijambo ry\'ibanga rishya.',
            'The new password must be different from the current password.' => 'Ijambo ry\'ibanga rishya rigomba kuba ritandukanye n\'irisanzwe.',

            // Shared validation
            'Please choose a member.' => 'Hitamo umunyamuryango.',
            'Please choose an active member.' => 'Hitamo umunyamuryango urakora.',
            'Name is required.' => 'Izina rirakenewe.',
            'Name must be 100 characters or fewer.' => 'Izina rigomba kuba inyuguti 100 cyangwa nkeya.',
            'Phone number is required.' => 'Numero ya telefoni irakenewe.',
            'Phone must be 7-20 digits (example: 0788123456).' => 'Telefoni igomba kuba imibare 7-20 (urugero: 0788123456).',
            'Please choose a valid join date.' => 'Hitamo itariki yinjiye yemewe.',
            'Please choose a valid status.' => 'Hitamo imiterere yemewe.',
            'Another member already uses this phone number.' => 'Undi munyamuryango asanzwe akoresha iyi numero ya telefoni.',
            'Could not save the member: %s' => 'Ntibyashobotse kubika umunyamuryango: %s',
            'Could not update the member: %s' => 'Ntibyashobotse kuvugurura umunyamuryango: %s',
            'Could not save the contribution: %s' => 'Ntibyashobotse kubika umusanzu: %s',
            'Could not save the loan: %s' => 'Ntibyashobotse kubika inguzanyo: %s',
            'Could not save the repayment: %s' => 'Ntibyashobotse kubika ubwishyu: %s',
            'Could not save the penalty: %s' => 'Ntibyashobotse kubika igihano: %s',
            'Could not save the meeting: %s' => 'Ntibyashobotse kubika inama: %s',
            'Could not save the settings: %s' => 'Ntibyashobotse kubika igenamiterere: %s',
            'Could not save the login: %s' => 'Ntibyashobotse kubika kwinjira: %s',

            // Counts and labels added while translating the pages
            '+ Record' => '+ Andika',
            '%d payment' => '%d ubwishyu',
            '%d payments' => '%d ubwishyu',
            '%d record' => '%d inyandiko',
            '%d records' => '%d inyandiko',
            '%d unpaid' => '%d ntizishyuwe',
            '%d still to pay' => '%d biracyishyurwa',
            'Please enter a valid amount.' => 'Andika amafaranga yemewe.',
            'Please enter a valid loan amount.' => 'Andika inguzanyo yemewe.',
            'Whole francs only, for example 1000.' => 'Amafaranga yuzuye gusa, urugero 1000.',
            'Secretary (admin)' => 'Umunyamabanga (admin)',
        );
    }

    return $rw;
}

// Translate a text into the current language. Extra arguments fill %s / %d.
function t($text)
{
    $out = $text;
    if (current_lang() === 'rw') {
        $dictionary = translations();
        if (isset($dictionary[$text])) {
            $out = $dictionary[$text];
        }
    }

    $args = array_slice(func_get_args(), 1);
    if (count($args) > 0) {
        $out = vsprintf($out, $args);
    }

    return $out;
}

// Pick the singular or plural English text, then translate it.
function tn($one, $many, $count)
{
    return t((int) $count === 1 ? $one : $many, $count);
}

// Format a date, translating English day and month names when needed.
function app_date($format, $timestamp = null)
{
    $timestamp = $timestamp === null ? time() : $timestamp;
    $text = date($format, $timestamp);

    if (current_lang() !== 'rw') {
        return $text;
    }

    $names = array(
        // Months
        'January' => 'Mutarama',
        'February' => 'Gashyantare',
        'March' => 'Werurwe',
        'April' => 'Mata',
        'May' => 'Gicurasi',
        'June' => 'Kamena',
        'July' => 'Nyakanga',
        'August' => 'Kanama',
        'September' => 'Nzeli',
        'October' => 'Ukwakira',
        'November' => 'Ugushyingo',
        'December' => 'Ukuboza',
        'Jan' => 'Mut',
        'Feb' => 'Gash',
        'Mar' => 'Wer',
        'Apr' => 'Mat',
        'Jun' => 'Kam',
        'Jul' => 'Nya',
        'Aug' => 'Kan',
        'Sep' => 'Nze',
        'Oct' => 'Ukw',
        'Nov' => 'Ugu',
        'Dec' => 'Uku',
        // Days
        'Monday' => 'Kuwa mbere',
        'Tuesday' => 'Kuwa kabiri',
        'Wednesday' => 'Kuwa gatatu',
        'Thursday' => 'Kuwa kane',
        'Friday' => 'Kuwa gatanu',
        'Saturday' => 'Kuwa gatandatu',
        'Sunday' => 'Ku cyumweru',
        'Mon' => 'Mbe',
        'Tue' => 'Kab',
        'Wed' => 'Gat',
        'Thu' => 'Kan',
        'Fri' => 'Gtn',
        'Sat' => 'Gtd',
        'Sun' => 'Cyu',
    );

    return strtr($text, $names);
}
