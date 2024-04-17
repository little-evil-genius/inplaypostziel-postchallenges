<?php
// Direktzugriff auf die Datei aus Sicherheitsgründen sperren
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
 
// HOOKS
$plugins->add_hook('admin_config_settings_change', 'postinggoal_settings_change');
$plugins->add_hook('admin_settings_print_peekers', 'postinggoal_settings_peek');
$plugins->add_hook("global_intermediate", "postinggoal_global");
$plugins->add_hook("misc_start", "postinggoal_misc");
$plugins->add_hook('usercp_start', 'postinggoal_usercp');
$plugins->add_hook('usercp_menu', 'postinggoal_usercpmenu', 40);
$plugins->add_hook("fetch_wol_activity_end", "postinggoal_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "postinggoal_online_location");

// Die Informationen, die im Pluginmanager angezeigt werden
function postinggoal_info(){
	return array(
		"name"		=> "Inplaypostziel & Post-Challenges",
		"description"	=> "Mit diesem Plugin kann für einen beliebigen Zeitraum ein Inplaypost-Ziel vorgegeben werden für das gesamte Forum. Zusätzlich haben User die Möglichkeit persönliche Post-Challenges zu starten.",
		"website"	=> "https://github.com/little-evil-genius/inplaypostziel-postchallenges",
        "author"	=> "little.evil.genius",
		"authorsite"	=> "https://storming-gates.de/member.php?action=profile&uid=1712",
		"version"	=> "1.0",
		"compatibility" => "18*"
	);
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin installiert wird (optional).
function postinggoal_install(){

    global $db, $cache, $mybb;

    // Accountswitcher muss vorhanden sein
    if (!function_exists('accountswitcher_is_installed')) {
		flash_message("Das Plugin <a href=\"http://doylecc.altervista.org/bb/downloads.php?dlid=26&cat=2\" target=\"_blank\">\"Enhanced Account Switcher\"</a> muss installiert sein!", 'error');
		admin_redirect('index.php?module=config-plugins');
	}

    // DATENBANKEN HINZUFÜGEN
    $db->query("CREATE TABLE ".TABLE_PREFIX."user_postchallenges(
        `pgid` int(10) NOT NULL AUTO_INCREMENT,
        `uid` int(11) unsigned NOT NULL,
        `posts` int(15) unsigned NOT NULL,
		`words` INT(15) unsigned DEFAULT '0' NOT NULL,
		`characters` INT(15) unsigned DEFAULT '0' NOT NULL,
        `days` int(5) unsigned NOT NULL,
        `startdate` int(15) unsigned utf8_general_ci NOT NULL,
        `enddate` int(15) unsigned NOT NULL,
        `reportstatus` int(1) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY(`pgid`),
        KEY `pgid` (`pgid`)
        )
        ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=1
    ");

	// EINSTELLUNGEN HINZUFÜGEN
    $maxdisporder = $db->fetch_field($db->query("SELECT MAX(disporder) FROM ".TABLE_PREFIX."settinggroups"), "MAX(disporder)");
	$setting_group = array(
		'name'          => 'postinggoal',
		'title'         => 'Inplaypostziel & Post-Challenges',
		'description'   => 'Einstellungen für das Inplaypostziel & die persönlichen Post-Challenges',
		'disporder'     => $maxdisporder+1,
		'isdefault'     => 0
	);
			
	$gid = $db->insert_query("settinggroups", $setting_group); 
			
	$setting_array = array(
		'postinggoal_inplayarea' => array(
			'title' => 'Inplayarea',
            'description' => 'Aus welchen Foren sollen die Post für den Marathon und die persönlichen Challenges gezählt werden? Es reicht aus, die übergeordneten Kategorien zu markieren.',
            'optionscode' => 'forumselect',
            'value' => '', // Default
            'disporder' => 1
		),
		'postinggoal_excludedarea' => array(
			'title' => 'ausgeschlossene Foren',
            'description' => 'Gibt es Foren, die innerhalb der ausgewählten Kategorie liegen aber nicht gezählt werden sollen (z.B. Communication)?',
            'optionscode' => 'forumselect',
            'value' => '', // Default
            'disporder' => 2
		),
        'postinggoal_challenges_activate' => array(
            'title' => 'persönliche Post-Challenges aktiv',
            'description' => 'Soll die Möglichkeit der persönlichen Post-Challenges aktiviert sein?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 3
        ),
        'postinggoal_challenges_overview' => array(
            'title' => 'Übersicht aller persönlichen Post-Challenges',
            'description' => 'Soll es einer Übersicht aller persönlichen Post-Challenges, sortiert nach aktuell und vergangen geben?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 4
        ),
        'postinggoal_challenges_overview_permissions' => array(
            'title' => 'Übersicht alle persönlichen Post-Challenges - Berechtigungen',
            'description' => 'Welche Gruppen dürfen sich die Übersicht aller persönlichen Post-Challenges ansehen?',
            'optionscode' => 'groupselect',
            'value' => '4', // Default
            'disporder' => 5
        ),
        'postinggoal_challenges_thread' => array(
            'title' => 'Meldethema für die abgeschlossenen persönlichen Post-Challenges',
            'description' => 'Gibt es ein Thema, in dem sich die User melden können, wenn sie ihre geschafft haben Post-Challenge? Bitte die entsprechende tid angeben. Wenn nicht gewünscht, 0 eintragen.',
            'optionscode' => 'numeric',
            'value' => '', // Default
            'disporder' => 6
        ),
        'postinggoal_marathon_activate' => array(
            'title' => 'Postmaraton aktiv',
            'description' => 'Läuft derzeit ein Postmarathon? Wenn ja, wird die Fortschrittanzeige im Index angezeigt und es besteht ein Zugriff auf die Ranglisten.',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 7
        ),
		'postinggoal_marathon_startdate' => array(
			'title' => 'Startdatum',
            'description' => 'Ab wann sollen die Posts für den Postmarathon gezählt werden? Format: DD.MM.YYYY',
            'optionscode' => 'text',
            'value' => '', // Default
            'disporder' => 8
		),
		'postinggoal_marathon_enddate' => array(
			'title' => 'Enddatum',
            'description' => 'Bis wann sollen die Posts für den Postmarathon gezählt werden? Format: DD.MM.YYYY',
            'optionscode' => 'text',
            'value' => '', // Default
            'disporder' => 9
		),
        'postinggoal_marathon_excludedaccounts' => array(
            'title' => 'ausgeschlossene Accounts',
            'description' => 'Von welchen Accounts sollen die Posts nicht beachtet werden für den Postmarathon? Gib durch Kommata getrennt die UIDs der Accounts an.',
            'optionscode' => 'text',
            'value' => '1', // Default
            'disporder' => 10
        ),
		'postinggoal_marathon_goal_post' => array(
			'title' => 'Post-Ziel',
            'description' => 'Wie hoch ist das Post-Ziel für den gewählten Zeitraum? (0 = kein Ziel)',
            'optionscode' => 'numeric',
            'value' => '30', // Default
            'disporder' => 11
		),
		'postinggoal_marathon_goal_word' => array(
			'title' => 'Wörter-Ziel',
            'description' => 'Wie hoch ist das Wörter-Ziel für den gewählten Zeitraum? (0 = kein Ziel)',
            'optionscode' => 'numeric',
            'value' => '30', // Default
            'disporder' => 12
		),
		'postinggoal_marathon_goal_character' => array(
			'title' => 'Zeichen-Ziel',
            'description' => 'Wie hoch ist das Zeichen-Ziel für den gewählten Zeitraum? (0 = kein Ziel)',
            'optionscode' => 'numeric',
            'value' => '30', // Default
            'disporder' => 13
		),
        'postinggoal_marathon_rangebar' => array(
            'title' => 'Fortschrittanzeige',
            'description' => 'Soll eine prozentuale Fortschrittanzeige für die einzelnen Ziele im Index angezeigt werden? Wenn kein Ziel (0) gesetzt wurde wird keine Fortschrittanzeige angezeigt.',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 14
        ),
        'postinggoal_marathon_toplist' => array(
            'title' => 'Rangliste aktivieren',
            'description' => 'Soll die Rangliste der Top-Spieler*innen und Top-Charaktere für diesen Postmarathon aktiv sein?',
            'optionscode' => 'yesno',
            'value' => '0', // Default
            'disporder' => 15
        ),
        'postinggoal_marathon_toplist_limit' => array(
            'title' => 'Limit der Rangliste',
            'description' => 'Soll nur eine bestimmte Anzahl an Top-Spieler*innen und Top-Charaktere des Zeitraums angezeigt werden (0 = Keine Beschränkung)?',
            'optionscode' => 'numeric',
            'value' => '0', // Default
            'disporder' => 16
        ),
        'postinggoal_playername' => array(
            'title' => 'Spielername',
            'description' => 'Wie lautet die FID / der Identifikator von dem Profilfeld/Steckbrieffeld für den Spielernamen?<br>
			<b>Hinweis:</b> Bei klassischen Profilfeldern muss eine Zahl eintragen werden. Bei dem Steckbrief-Plugin von Risuena muss der Name/Identifikator des Felds eingetragen werden.',
            'optionscode' => 'text',
            'value' => '4', // Default
            'disporder' => 17
        ),
	);
			
	foreach($setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid']  = $gid;
		$db->insert_query('settings', $setting);
	}
	rebuild_settings();

    // TEMPLATES ERSTELLEN
    // Template Gruppe für jedes Design erstellen
    $templategroup = array(
        "prefix" => "postinggoal",
        "title" => $db->escape_string("Inplaypostziel & Post-Challenges"),
    );
    $db->insert_query("templategroups", $templategroup);

    $insert_array = array(
        'title'		=> 'postinggoal_challenges_overview_active',
        'template'	=> $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} - {$lang->postinggoal_overview_nav_active}</title>
            {$headerinclude}</head>
        <body>
            {$header}
            <table width="100%" cellspacing="5" cellpadding="0">
                <tr>
                    <td valign="top">
                        <div id="postinggoal_toplist">
                            {$navigation}
                            <div class="postinggoal_toplist-main">
                                <div class="postinggoal_toplist-headline">{$lang->postinggoal_overview_nav_active}</div>
                                <div class="postinggoal_toplist-result">
                                    {$multipage}
                                    <div class="postinggoal_toplist-table">
                                        <div class="postinggoal_toplist-table-row header">
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_overview_head_player}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_overview_head_goal}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_overview_head_progress}</div>
                                        </div>
                                        {$challenges_active_none}
                                        {$challenges_bit}
                                    </div>
                                    {$multipage}
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
        'sid'		=> '-2',
        'dateline'	=> TIME_NOW
    );
    $db->insert_query("templates", $insert_array);    
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_overview_bit',
        'template'    => $db->escape_string('<div class="postinggoal_toplist-table-row">
        <div class="postinggoal_toplist-table-cell">{$playername}</div>
        <div class="postinggoal_toplist-table-cell">{$goal_headline}</div>
        <div class="postinggoal_toplist-table-cell">{$goal_status}<br>{$status_result}</div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);    
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_overview_finished',
        'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} - {$lang->postinggoal_overview_nav_finished}</title>
            {$headerinclude}</head>
        <body>
            {$header}
            <table width="100%" cellspacing="5" cellpadding="0">
                <tr>
                    <td valign="top">
                        <div id="postinggoal_toplist">
                            {$navigation}
                            <div class="postinggoal_toplist-main">
                                <div class="postinggoal_toplist-headline">{$lang->postinggoal_overview_nav_finished}</div>
                                <div class="postinggoal_toplist-result">
                                    {$multipage}
                                    <div class="postinggoal_toplist-table">
                                        <div class="postinggoal_toplist-table-row header">
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_overview_head_player}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_overview_head_goal}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_overview_head_result}</div>
                                        </div>
                                        {$challenges_finished_none}
                                        {$challenges_bit}
                                    </div>
                                    {$multipage}
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);    
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_overview_navigation',
        'template'    => $db->escape_string('<div class="postinggoal_toplist-navigation">
        <div class="postinggoal_toplist-navigation-headline">{$lang->postinggoal_overview_nav}</div>
        <div class="postinggoal_toplist-navigation-item"><a href="misc.php?action=postchallenges_overview_active">{$lang->postinggoal_overview_nav_active}</a></div>  
        <div class="postinggoal_toplist-navigation-item"><a href="misc.php?action=postchallenges_overview_finished">{$lang->postinggoal_overview_nav_finished}</a></div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);  
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp',
        'template'    => $db->escape_string('<html>
        <head>
            <title>{$lang->user_cp} - {$lang->postinggoal_usercp_nav}</title>
            {$headerinclude}
        </head>
        <body>
            {$header}
            <table width="100%" border="0" align="center">
                <tr>
                    {$usercpnav}
                    <td valign="top">
                        <div id="postinggoal_usercp">
                            <div class="postinggoal_usercp-headline">{$lang->postinggoal_usercp_nav}</div>
                            <div class="postinggoal_usercp-desc">{$lang->postinggoal_usercp_desc}</div>
                            {$challenge_add}
                            <div class="postinggoal_usercp-headline">{$lang->postinggoal_usercp_challenge_active_headline}</div>
                            <div class="postinggoal_usercp-bit">
                                {$challenge_active}
                            </div>
                            <div class="postinggoal_usercp-headline">{$lang->postinggoal_usercp_challenge_finished_headline}</div>
                            <div class="postinggoal_usercp-bit">
                                <div class="postinggoal_usercp-challene_past-headline">
                                    <div class="postinggoal_usercp-challene_past-headline-bit">{$lang->postinggoal_usercp_challenge_finished_headline_goal}</div>
                                    <div class="postinggoal_usercp-challene_past-headline-bit">{$lang->postinggoal_usercp_challenge_finished_headline_result}</div>
                                </div>
                                {$challenge_finished_none}
                                {$challenge_finished}
                                {$multipage}
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);   
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp_active',
        'template'    => $db->escape_string('<div class="postinggoal_challenge-headline">
        {$goal_headline}
    </div>
    <div class="postinggoal_challenge-goals">
        <div class="postinggoal_challenge-counter-bit">
            <div class="postinggoal_challenge-count-progress">{$countposts}</div>
            <div class="postinggoal_challenge-count-goal">{$post_challenge}</div>
        </div>
        <div class="postinggoal_challenge-counter-bit">
            <div class="postinggoal_challenge-count-progress">{$countwords}</div>
            <div class="postinggoal_challenge-count-goal">{$word_challenge}</div>
        </div>
        <div class="postinggoal_challenge-counter-bit">
            <div class="postinggoal_challenge-count-progress">{$countcharacters}</div>
            <div class="postinggoal_challenge-count-goal">{$character_challenge}</div>
        </div>
    </div>
    <div class="postinggoal_challenge-notice">
        {$goal_status}{$challenge_report}
    </div>
    <div class="postinggoal_challenge-statistic">
        <div class="postinggoal_challenge-table">
            <div class="postinggoal_challenge-table-row header">
                <div class="postinggoal_challenge-table-cell">{$lang->postinggoal_usercp_challenge_characterstat_name}</div>
                <div class="postinggoal_challenge-table-cell">{$lang->postinggoal_usercp_challenge_characterstat_post}</div>
                <div class="postinggoal_challenge-table-cell">{$lang->postinggoal_usercp_challenge_characterstat_word}</div>
                <div class="postinggoal_challenge-table-cell">{$lang->postinggoal_usercp_challenge_characterstat_character}</div>
            </div>
            {$characters_bit}
        </div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);    
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp_add',
        'template'    => $db->escape_string('<div class="postinggoal_usercp_add">
        <div class="postinggoal_usercp_add-headline">{$lang->postinggoal_usercp_challenge_add_headline}</div>
        <form id="new_postchallenge" method="post" action="usercp.php?action=postinggoal_addchallenge">
            <div class="postinggoal_usercp_add-container">
                <div class="postinggoal_usercp_add-bit">
                    <div class="postinggoal_usercp_add-bit-headline">{$lang->postinggoal_usercp_challenge_add_posts}</div>
                    <div class="postinggoal_usercp_add-bit-input">
                        <input type="number" id="postgoal" name="postgoal" min="1" class="textbox" required/>
                    </div>
                </div>
                <div class="postinggoal_usercp_add-bit">
                    <div class="postinggoal_usercp_add-bit-headline">{$lang->postinggoal_usercp_challenge_add_words}</div>
                    <div class="postinggoal_usercp_add-bit-input">
                        <input type="number" id="wordgoal" name="wortgoal" min="0" class="textbox"/>
                    </div>
                </div>
                <div class="postinggoal_usercp_add-bit">
                    <div class="postinggoal_usercp_add-bit-headline">{$lang->postinggoal_usercp_challenge_add_characters}</div>
                    <div class="postinggoal_usercp_add-bit-input">
                        <input type="number" id="chargoal" name="chargoal" min="0" class="textbox"/>
                    </div>
                </div>
                <div class="postinggoal_usercp_add-bit">
                    <div class="postinggoal_usercp_add-bit-headline">{$lang->postinggoal_usercp_challenge_add_days}</div>
                    <div class="postinggoal_usercp_add-bit-input">
                        <input type="number" id="enddate" name="enddate" min="1" class="textbox" required/>
                    </div>
                </div>
            </div>
            <center>
                <input type="submit" name="postinggoal_postchallenge" value="{$lang->postinggoal_usercp_challenge_add_button}" id="postinggoal_postchallenge" class="button">
            </center>
        </form>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);  
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp_characters',
        'template'    => $db->escape_string('<div class="postinggoal_challenge-table-row">
        <div class="postinggoal_challenge-table-cell">{$charactername}</div>
        <div class="postinggoal_challenge-table-cell">{$countposts_chara}</div>
        <div class="postinggoal_challenge-table-cell">{$countwords_chara}</div>
        <div class="postinggoal_challenge-table-cell">{$countcharacters_chara}</div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);   
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp_finished',
        'template'    => $db->escape_string('<div class="postinggoal_toplist-table-row">
        <div class="postinggoal_toplist-table-cell">{$goal_headline}</div>
        <div class="postinggoal_toplist-table-cell">
            {$goal_status}<br>
            {$status_result}
            {$challenge_report}
        </div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);     
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp_nav',
        'template'    => $db->escape_string('<tr>
        <td class="trow1 smalltext">
            <a href="usercp.php?action=postchallenges" class="usercp_nav_item usercp_nav_pmfolders">{$lang->postinggoal_usercp_nav}</a>
        </td>
    </tr>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);     
    
    $insert_array = array(
        'title'        => 'postinggoal_challenges_usercp_report',
        'template'    => $db->escape_string('<br>
        <form action="usercp.php?action=postinggoal_challenge_ready" method="post" id="postinggoal_ucp_form">
            {$challenge_inputs}
            <input type="submit" class="button" name="postinggoal_challenge_ready" value="{$button_value}" {$button_onClick}>
        </form>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);     
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_index',
        'template'    => $db->escape_string('<div class="postinggoal_index">
        <div class="postinggoal_index-headline">{$postgoal_headline}</div>
        <div class="postinggoal_index-counter">
            {$counter_post}
            {$counter_word}
            {$counter_character}
        </div>
        {$toplist_link}
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);      
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_index_bit_count',
        'template'    => $db->escape_string('<div class="postinggoal_index-counter-bit">
        <div class="postinggoal_index-count-progress">{$count_progress}</div>
        <div class="postinggoal_index-count-goal">{$count_goal}</div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);  
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_index_bit_goal',
        'template'    => $db->escape_string('<div class="postinggoal_index-counter-bit">
        <div class="postinggoal_index-count-progress">{$count_progress}</div>
        <div class="postinggoal_index-count-goal">{$count_goal}</div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array); 
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_index_bit_progressbar',
        'template'    => $db->escape_string('<div class="postinggoal_index-counter-bit">
        <div class="postinggoal_index-count-progress">
            <div class="postinggoal_index-progressbar" style="--progress: {$progress_percentage}%;"> 
                {$progress_percentage}%
            </div>
        </div>
        <div class="postinggoal_index-count-goal">
            {$count_goal}
        </div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);  
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_index_toplist',
        'template'    => $db->escape_string('<div class="postinggoal_index-toplist"><span class="smalltext"><a href="misc.php?action=postgoals_character">{$lang->postinggoal_index_toplist_character}</a> | <a href="misc.php?action=postgoals_player">{$lang->postinggoal_index_toplist_player}</a></span></div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array); 
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_toplist_bit',
        'template'    => $db->escape_string('<div class="postinggoal_toplist-table-row">
        <div class="postinggoal_toplist-table-cell">{$name}</div>
        <div class="postinggoal_toplist-table-cell">{$countposts}</div>
        <div class="postinggoal_toplist-table-cell">{$countwords}</div>
        <div class="postinggoal_toplist-table-cell">{$countcharacters}</div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array); 
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_toplist_character',
        'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} - {$lang->postinggoal_toplist_character}</title>
            {$headerinclude}</head>
        <body>
            {$header}
            <table width="100%" cellspacing="5" cellpadding="0">
                <tr>
                    <td valign="top">
                        <div id="postinggoal_toplist">
                            {$navigation}
                            <div class="postinggoal_toplist-main">
                                <div class="postinggoal_toplist-headline">{$lang->postinggoal_toplist_character}</div>
                                <div class="postinggoal_toplist-result">
                                    <div class="postinggoal_toplist-table">
                                        <div class="postinggoal_toplist-table-row header">
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_character_head}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_head_posts}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_head_words}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_head_char}</div>
                                        </div>
                                        {$toplist_characters}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array); 
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_toplist_navigation',
        'template'    => $db->escape_string('<div class="postinggoal_toplist-navigation">
        <div class="postinggoal_toplist-navigation-headline">{$lang->postinggoal_toplist_nav}</div>
        <div class="postinggoal_toplist-navigation-item"><a href="misc.php?action=postgoals_character">{$lang->postinggoal_toplist_nav_character}</a></div>  
        <div class="postinggoal_toplist-navigation-item"><a href="misc.php?action=postgoals_player">{$lang->postinggoal_toplist_nav_player}</a></div>
    </div>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array); 
    
    $insert_array = array(
        'title'        => 'postinggoal_marathon_toplist_player',
        'template'    => $db->escape_string('<html>
        <head>
            <title>{$mybb->settings[\'bbname\']} - {$lang->postinggoal_toplist_player}</title>
            {$headerinclude}</head>
        <body>
            {$header}
            <table width="100%" cellspacing="5" cellpadding="0">
                <tr>
                    <td valign="top">
                        <div id="postinggoal_toplist">
                            {$navigation}
                            <div class="postinggoal_toplist-main">
                                <div class="postinggoal_toplist-headline">{$lang->postinggoal_toplist_player}</div>
                                <div class="postinggoal_toplist-result">
                                    <div class="postinggoal_toplist-table">
                                        <div class="postinggoal_toplist-table-row header">
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_player_head}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_head_posts}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_head_words}</div>
                                            <div class="postinggoal_toplist-table-cell">{$lang->postinggoal_toplist_head_char}</div>
                                        </div>
                                        {$toplist_player}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            {$footer}
        </body>
    </html>'),
        'sid'        => '-2',
        'dateline'    => TIME_NOW
    );
    $db->insert_query("templates", $insert_array); 

    // STYLESHEET HINZUFÜGEN
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    $css = array(
        'name' => '.postinggoal_index {
            background: #fff;
            width: 100%;
            margin: auto auto;
            border: 1px solid #ccc;
            padding: 1px;
            -moz-border-radius: 7px;
            -webkit-border-radius: 7px;
            border-radius: 7px;
        }
        
        .postinggoal_index-headline {
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            border-bottom: 1px solid #263c30;
            padding: 8px;
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        
        .postinggoal_index-counter {
            display: flex;
            flex-wrap: nowrap;
            background: #f5f5f5;
            border: 1px solid;
            border-color: #fff #ddd #ddd #fff;
            padding: 10px 0;
            justify-content: space-around;
            align-items: center; 
        }
        
        .postinggoal_index-counter-bit {
            text-align: center;
        }
        
        .postinggoal_index-count-progress {
            font-weight: bold;
            font-size: 20px;
            display: flex;
            justify-content: center;
        }
        
        .postinggoal_index-toplist {
            border-top: 1px solid #fff;
            padding: 6px;
            background: #ddd;
            color: #666;
            text-align: right;
            -moz-border-radius-bottomright: 6px;
            -webkit-border-bottom-right-radius: 6px;
            border-bottom-right-radius: 6px;
            -moz-border-radius-bottomleft: 6px;
            -webkit-border-bottom-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }
        
        .postinggoal_index-toplist a:link,
        .postinggoal_index-toplist a:visited,
        .postinggoal_index-toplist a:active {	
            color: #444;
            text-decoration: none;
        }
        
        .postinggoal_index-toplist a:hover {	
            text-decoration: underline;
        }
        
        .postinggoal_index-progressbar {
            width: 50px;
            height: 50px;
            display: grid;
            place-items: center;
            position: relative;
            font-weight: 700;
            font-size: 13px;
        }
        
        .postinggoal_index-progressbar::before {
            content: \\\'\\\';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            border-radius: 50%;
            background: conic-gradient( #f2f2f2, #f2f2f2, #f2f2f2 var(--progress, 0%), hsl(0, 0%, 70.2%) var(--progress, 0%) 100%);
            mask-image: radial-gradient(transparent 62%, black calc(62% + 0.5px));
        }
        
        #postinggoal_toplist {
            width: 100%;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .postinggoal_toplist-navigation {
            width: 20%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            background: #fff;
            border: 1px solid #ccc;
            padding: 1px;
            -moz-border-radius: 7px;
            -webkit-border-radius: 7px;
            border-radius: 7px;
        }
        
        .postinggoal_toplist-navigation-headline {
            min-height: 50px;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            padding: 0 5px;
            box-sizing: border-box;
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        
        .postinggoal_toplist-navigation-item {
            min-height: 25px;
            width: 100%;
            margin: 0 auto;
            padding: 5px 20px;
            display: flex;
            align-items: center;
            box-sizing: border-box;
            border-bottom: 1px solid #ddd;
            background: #f5f5f5;
        }
        
        .postinggoal_toplist-navigation-item:last-child {
            -moz-border-radius-bottomright: 6px;
            -webkit-border-bottom-right-radius: 6px;
            border-bottom-right-radius: 6px;
            -moz-border-radius-bottomleft: 6px;
            -webkit-border-bottom-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }
        
        .postinggoal_toplist-main {
            width: 80%;
            box-sizing: border-box;
            background: #fff;
            border: 1px solid #ccc;
            padding: 1px;
            -moz-border-radius: 7px;
            -webkit-border-radius: 7px;
            border-radius: 7px;
        }
        
        .postinggoal_toplist-headline {
            height: 50px;
            width: 100%;
            font-size: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            text-transform: uppercase;
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        
        .postinggoal_toplist-result {
            background: #f5f5f5;
            border: 1px solid;
            border-color: #fff #ddd #ddd #fff;
            padding: 10px;
            text-align: justify;
            -moz-border-radius-bottomright: 6px;
            -webkit-border-bottom-right-radius: 6px;
            border-bottom-right-radius: 6px;
            -moz-border-radius-bottomleft: 6px;
            -webkit-border-bottom-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }
        
        .postinggoal_toplist-table {
            display: flex;
            flex-direction: column;
            border: 1px solid #ccc;
            padding: 1px;
            -moz-border-radius: 7px;
            -webkit-border-radius: 7px;
            border-radius: 7px;
        }
        
        .postinggoal_toplist-table-row {
            display: flex;
            border-bottom: 1px solid #ccc;
        }
        
        .postinggoal_toplist-table-row.header {
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            border-bottom: 1px solid #263c30;
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        
        .postinggoal_toplist-table-row:last-child {
            -moz-border-radius-bottomright: 6px;
            -webkit-border-bottom-right-radius: 6px;
            border-bottom-right-radius: 6px;
            -moz-border-radius-bottomleft: 6px;
            -webkit-border-bottom-left-radius: 6px;
            border-bottom-left-radius: 6px;
            border-bottom: 0;
        }
        
        .postinggoal_toplist-table-cell {
            flex: 1;
            padding: 5px;
            border-right: 1px solid #ccc;
        }
        
        .postinggoal_toplist-table-cell:last-child {
            border-right: 0;
        }
        
        .postinggoal_goalstatus_reached {
            color: green;
            font-weight: bold;
        }
        
        .postinggoal_goalstatus_notreached {
            color:red;
            font-weight: bold;
        }
        .postinggoal_smalltext {
            font-size: 11px;
        }
        
        #postinggoal_usercp {
            background: #fff;
            width: 100%;
            margin: auto auto;
            border: 1px solid #ccc;
            padding: 1px;
            -moz-border-radius: 7px;
            -webkit-border-radius: 7px;
            border-radius: 7px;
        }
        
        .postinggoal_usercp-headline {
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            border-bottom: 1px solid #263c30;
            padding: 8px;
            font-weight: bold;
        }
        
        .postinggoal_usercp-headline:first-child {
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        
        .postinggoal_usercp-desc {
            background: #f5f5f5;
            border: 1px solid;
            border-color: #fff #ddd #ddd #fff;
            text-align: justify;
            line-height: 180%;
            padding: 20px 40px;
        }
        
        .postinggoal_usercp-bit {
            background: #f5f5f5;
            border-color: #fff #ddd #ddd #fff;
            -moz-border-radius-bottomleft: 6px;
            -webkit-border-bottom-left-radius: 6px;
            border-bottom-left-radius: 6px;
            -moz-border-radius-bottomright: 6px;
            -webkit-border-bottom-right-radius: 6px;
            border-bottom-right-radius: 6px;
            padding: 0 0 5px;
        }
        
        .postinggoal_usercp-bit center {
            padding: 10px 0;
        }
        
        .postinggoal_usercp_add {
            background: #f5f5f5;
            padding-bottom: 10px;
        }
        
        .postinggoal_usercp_add-headline {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        
        .postinggoal_usercp_add-container {
            display: flex;
            justify-content: space-around;
            width: 90%;
            margin: 10px auto;
            gap: 5px;
        }
        
        .postinggoal_usercp_add-bit {
            width: 100%;
            text-align: center;
        }
        
        .postinggoal_usercp_add-bit-headline {
            padding: 6px;
            background: #ddd;
            color: #666;
        }
        
        .postinggoal_usercp_add-bit-input {
            margin: 5px;
        }
        
        .postinggoal_challenge-headline {
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
        }
        
        .postinggoal_challenge-goals {
            display: flex;
            flex-wrap: nowrap;
            justify-content: space-around;
            align-items: center;
            padding: 10px 0;
        }
        
        .postinggoal_challenge-counter-bit {
            text-align: center;
            width: 33.4%;
        }
        
        .postinggoal_challenge-count-progress {
            font-weight: bold;
            font-size: 20px;
            display: flex;
            justify-content: center;
        }
        
        .postinggoal_challenge-notice {
            text-align: center;
            padding: 5px 0;
        }
        
        .postinggoal_challenge-statistic {
            padding: 5px 10px;
        }
        
        .postinggoal_challenge-table {
            display: flex;
            flex-direction: column;
            -moz-border-radius: 7px;
            -webkit-border-radius: 7px;
            border-radius: 7px;
        }
        
        .postinggoal_challenge-table-row {
            display: flex;
            border-bottom: 1px solid #ccc;
        }
        
        .postinggoal_challenge-table-row.header {
            background: #0066a2 url(../../../images/thead.png) top left repeat-x;
            color: #ffffff;
            border-bottom: 1px solid #263c30;
            -moz-border-radius-topleft: 6px;
            -moz-border-radius-topright: 6px;
            -webkit-border-top-left-radius: 6px;
            -webkit-border-top-right-radius: 6px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }
        
        .postinggoal_challenge-table-row:last-child {
            border-bottom: 0;
        }
        
        .postinggoal_challenge-table-cell {
            flex: 1;
            padding: 5px;
        }
        
        .postinggoal_usercp-challene_past-headline {
            display: flex;
            justify-content: space-between;
        }
        
        .postinggoal_usercp-challene_past-headline-bit {
            width: 50%;
            background: #0f0f0f url(../../../images/tcat.png) repeat-x;
            color: #fff;
            border-top: 1px solid #444;
            border-bottom: 1px solid #000;
            padding: 6px;
            font-size: 12px;
            font-weight: bold;
        }',
        'cachefile' => $db->escape_string(str_replace('/', '', 'postinggoal.css')),
        'lastmodified' => time()
    );
    
    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "postinggoal.css"), "sid = '".$sid."'", 1);

    $tids = $db->simple_select("themes", "tid");
    while($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }
}
 
// Funktion zur Überprüfung des Installationsstatus; liefert true zurürck, wenn Plugin installiert, sonst false (optional).
function postinggoal_is_installed(){

    global $db, $cache, $mybb;
  
	if($db->table_exists("user_postchallenges"))  {
		return true;
	}
	return false;
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin deinstalliert wird (optional).
function postinggoal_uninstall(){
	
    global $db, $cache;

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

    //DATENBANK LÖSCHEN
    if($db->table_exists("user_postchallenges"))
    {
        $db->drop_table("user_postchallenges");
    }
    
    // EINSTELLUNGEN LÖSCHEN
    $db->delete_query('settings', "name LIKE 'postinggoal%'");
    $db->delete_query('settinggroups', "name = 'postinggoal'");

    rebuild_settings();

    // TEMPLATGRUPPE LÖSCHEN
    $db->delete_query("templategroups", "prefix = 'postinggoal'");

    // TEMPLATES LÖSCHEN
    $db->delete_query("templates", "title LIKE 'postinggoal%'");

	// STYLESHEET ENTFERNEN
	$db->delete_query("themestylesheets", "name = 'postinggoal.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
    }
} 
 
// Diese Funktion wird aufgerufen, wenn das Plugin aktiviert wird.
function postinggoal_marathon_activate(){
    
    // VARIABLE EINFÜGEN
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("index", "#".preg_quote('{$footer}')."#i", '{$postinggoal_marathon}{$footer}');
}
 
// Diese Funktion wird aufgerufen, wenn das Plugin deaktiviert wird.
function postinggoal_deactivate(){

    // VARIABLE ENTFERNEN
    include MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("index", "#".preg_quote('{$postinggoal_marathon}')."#i", '', 0);
}

#####################################
### THE BIG MAGIC - THE FUNCTIONS ###
#####################################

// ADMIN-CP PEEKER
function postinggoal_settings_change(){
    
    global $db, $mybb, $postinggoal_settings_peeker;

    $result = $db->simple_select('settinggroups', 'gid', "name='postinggoal'", array("limit" => 1));
    $group = $db->fetch_array($result);
    $postinggoal_settings_peeker = ($mybb->get_input('gid') == $group['gid']) && ($mybb->request_method != 'post');
}
function postinggoal_settings_peek(&$peekers){

    global $postinggoal_settings_peeker;

    // Post-Challenges Aktiv
	if ($postinggoal_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_postinggoal_challenges_activate"), $("#row_setting_postinggoal_challenges_overview, #row_setting_postinggoal_challenges_overview_permissions, #row_setting_postinggoal_challenges_thread"),/1/,true)';
    }
    // Post-Challenges Übersicht
	if ($postinggoal_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_postinggoal_challenges_overview"), $("#row_setting_postinggoal_challenges_overview_permissions"),/1/,true)';
    }
    // Marathon Aktiv
	if ($postinggoal_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_postinggoal_marathon_activate"), $("#row_setting_postinggoal_marathon_startdate, #row_setting_postinggoal_marathon_enddate, #row_setting_postinggoal_marathon_excludedaccounts, #row_setting_postinggoal_marathon_goal_post, #row_setting_postinggoal_marathon_goal_word, #row_setting_postinggoal_marathon_goal_character, #row_setting_postinggoal_marathon_rangebar, #row_setting_postinggoal_marathon_toplist, #row_setting_postinggoal_marathon_toplist_limit"),/1/,true)';
    }
	// Marathon Rangliste
    if ($postinggoal_settings_peeker) {
        $peekers[] = 'new Peeker($(".setting_postinggoal_marathon_toplist"), $("#row_setting_postinggoal_marathon_toplist_limit"),/1/,true)';
    }
}

// INDEX ANZEIGE
function postinggoal_global(){
    
    global $db, $cache, $mybb, $templates, $headerinclude, $theme, $header, $footer, $lang, $postinggoal_marathon;

    // SPRACHDATEI
	$lang->load('postinggoal');

    // EINSTELLUNGEN ZIEHEN
	// Plugin aktiv
    $activate_marathon = $mybb->settings['postinggoal_marathon_activate'];
	// Datum Angabe
    $startdate = $mybb->settings['postinggoal_marathon_startdate'];
	$start_timestamp = strtotime($startdate." 00:00:00");
    $enddate = $mybb->settings['postinggoal_marathon_enddate'];
    $end_timestamp = strtotime($enddate." 23:59:59");
    // Inplaybereich
    $inplayarea = $mybb->settings['postinggoal_inplayarea'];
    $selectedforums = explode(",", $inplayarea);
	$excludedarea = $mybb->settings['postinggoal_excludedarea'];
	// ausgeschlossene Accounts
	$excludedaccounts = str_replace(", ", ",", $mybb->settings['postinggoal_marathon_excludedaccounts']);
    // Ziele
    $goal_post_setting = $mybb->settings['postinggoal_marathon_goal_post'];
    $goal_word_setting = $mybb->settings['postinggoal_marathon_goal_word'];
    $goal_character_setting = $mybb->settings['postinggoal_marathon_goal_character'];
    // Fortschrittanzeige
    $rangebar_setting = $mybb->settings['postinggoal_marathon_rangebar'];
    // Rangliste
    $toplist_activate = $mybb->settings['postinggoal_marathon_toplist'];

    // zurück, wenn es nicht aktiv ist
    $postinggoal_marathon = "";
    if ($activate_marathon == 0) return;

    if(!empty($excludedarea)) {
        $excludedarea_sql = "AND p.fid NOT IN (".$excludedarea.")";
    } else {
        $excludedarea_sql = "";
    }

    if(!empty($excludedaccounts)) {
        $excludedaccounts_sql = "AND p.uid NOT IN (".$excludedaccounts.")";
    } else {
        $excludedaccounts_sql = "";
    }

    // FID-ARRAY BILDEN
    $parentlist_sql = "AND (";
    foreach ($selectedforums as $selected) {
        $parentlist_sql .= "(concat(',',f.parentlist,',') LIKE '%,".$selected.",%') OR ";
    }
    $parentlist_sql = substr($parentlist_sql, 0, -4).")";

	$post_query = $db->query("SELECT message FROM ".TABLE_PREFIX."posts p
    LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
    WHERE p.dateline BETWEEN '".$start_timestamp."' AND '".$end_timestamp."'
    ".$parentlist_sql."
    ".$excludedarea_sql."
    AND p.visible = '1'
    ".$excludedaccounts_sql."
    ");

    $countposts = $countwords = $countcharacters = 0;
    while ($post = $db->fetch_array($post_query)) {
        // Post
        $countposts++;
        // Wörter
        $countwords += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
        // Zeichen
        $countcharacters += strlen($post['message']);
    }

    // Post-Ziel
    if (!empty($goal_post_setting)) {
        if ($rangebar_setting == 1) {
            $progress_percentage = round(($countposts / $goal_post_setting) * 100, 0);
            $count_goal = $lang->sprintf($lang->postinggoal_index_bit_rangebar_posts, number_format($countposts, '0', ',', '.'), number_format($goal_post_setting, '0', ',', '.'));
            eval("\$counter_post = \"".$templates->get("postinggoal_marathon_index_bit_progressbar")."\";");
        } else {
            $count_progress = number_format($countposts, '0', ',', '.');
            $count_goal = $lang->sprintf($lang->postinggoal_index_bit_goal_posts, number_format($goal_post_setting, '0', ',', '.'));
            eval("\$counter_post = \"".$templates->get("postinggoal_marathon_index_bit_goal")."\";");
        }
    } else {
        $count_progress = number_format($countposts, '0', ',', '.');
        $count_goal = $lang->postinggoal_index_bit_count_posts;
        eval("\$counter_post = \"".$templates->get("postinggoal_marathon_index_bit_count")."\";");
    }

    // Wörter-Ziel
    if (!empty($goal_word_setting)) {
        if ($rangebar_setting == 1) {
            $progress_percentage = round(($countwords / $goal_word_setting) * 100, 0);
            $count_goal = $lang->sprintf($lang->postinggoal_index_bit_rangebar_words, number_format($countwords, '0', ',', '.'), number_format($goal_word_setting, '0', ',', '.'));
            eval("\$counter_word = \"".$templates->get("postinggoal_marathon_index_bit_progressbar")."\";");
        } else {
            $count_progress = number_format($countwords, '0', ',', '.');
            $count_goal = $lang->sprintf($lang->postinggoal_index_bit_goal_words, number_format($goal_word_setting, '0', ',', '.'));
            eval("\$counter_word = \"".$templates->get("postinggoal_marathon_index_bit_goal")."\";");
        }
    } else {
        $count_progress = number_format($countwords, '0', ',', '.');
        $count_goal = $lang->postinggoal_index_bit_count_words;
        eval("\$counter_word = \"".$templates->get("postinggoal_marathon_index_bit_count")."\";");
    }

    // Zeichen-Ziel
    if (!empty($goal_character_setting)) {
        if ($rangebar_setting == 1) {
            $progress_percentage = round(($countcharacters / $goal_character_setting) * 100, 0);
            $count_goal = $lang->sprintf($lang->postinggoal_index_bit_rangebar_char, number_format($countcharacters, '0', ',', '.'), number_format($goal_character_setting, '0', ',', '.'));
            eval("\$counter_character = \"".$templates->get("postinggoal_marathon_index_bit_progressbar")."\";");
        } else {
            $count_progress = number_format($countcharacters, '0', ',', '.');
            $count_goal = $lang->sprintf($lang->postinggoal_index_bit_goal_char, number_format($goal_character_setting, '0', ',', '.'));
            eval("\$counter_character = \"".$templates->get("postinggoal_marathon_index_bit_goal")."\";");
        }
    } else {
        $count_progress = number_format($countcharacters, '0', ',', '.');
        $count_goal = $lang->postinggoal_index_bit_count_char;
        eval("\$counter_character = \"".$templates->get("postinggoal_marathon_index_bit_count")."\";");
    }

    if ($toplist_activate == 1) {
        eval("\$toplist_link = \"".$templates->get("postinggoal_marathon_index_toplist")."\";");
    } else {
        $toplist_link = "";
    }

    $postgoal_headline = $lang->sprintf($lang->postinggoal_index_headline, date($mybb->settings['dateformat'], $start_timestamp), date($mybb->settings['dateformat'], $end_timestamp));

	eval("\$postinggoal_marathon = \"".$templates->get("postinggoal_marathon_index")."\";");
}

// TOPLISTE & ÜBERSICHT
function postinggoal_misc(){

    global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer, $page, $navigation;

    // SPRACHDATEI
	$lang->load('postinggoal');

    $mybb->input['action'] = $mybb->get_input('action');

    // EINSTELLUNGEN ZIEHEN
	// Marathon aktiv
    $activate_marathon = $mybb->settings['postinggoal_marathon_activate'];
	// Datum Angabe
    $startdate = $mybb->settings['postinggoal_marathon_startdate'];
	$start_timestamp = strtotime($startdate." 00:00:00");
    $enddate = $mybb->settings['postinggoal_marathon_enddate'];
    $end_timestamp = strtotime($enddate." 23:59:59");
    // Inplaybereich
    $inplayarea = $mybb->settings['postinggoal_inplayarea'];
    $selectedforums = explode(",", $inplayarea);
	$excludedarea = $mybb->settings['postinggoal_excludedarea'];
	// ausgeschlossene Accounts
	$excludedaccounts = str_replace(", ", ",", $mybb->settings['postinggoal_marathon_excludedaccounts']);
    // Rangliste
    $toplist_activate = $mybb->settings['postinggoal_marathon_toplist'];
    $toplist_limit = $mybb->settings['postinggoal_marathon_toplist_limit'];
    $playername_setting = $mybb->settings['postinggoal_playername'];
    // Challenges aktiv
    $activate_challenges = $mybb->settings['postinggoal_challenges_activate'];
    $overview_activate = $mybb->settings['postinggoal_challenges_overview'];
    $overview_permissions = $mybb->settings['postinggoal_challenges_overview_permissions'];

    // Heute
    $today_time = time();
    $today_date  = new DateTime(date("Y-m-d", time()));

    // ausgeschlossene Foren
    if(!empty($excludedarea)) {
        $excludedarea_sql = "AND p.fid NOT IN (".$excludedarea.")";
    } else {
        $excludedarea_sql = "";
    }

    // ausgeschlossene Accounts
    if(!empty($excludedaccounts)) {
        $excludedaccounts_sql = "AND p.uid NOT IN (".$excludedaccounts.")";
    } else {
        $excludedaccounts_sql = "";
    }

    // FID-ARRAY BILDEN
    $parentlist_sql = "AND (";
    foreach ($selectedforums as $selected) {
        $parentlist_sql .= "(concat(',',f.parentlist,',') LIKE '%,".$selected.",%') OR ";
    }
    $parentlist_sql = substr($parentlist_sql, 0, -4).")";

    // TOPLISTE NACH CHARAKTEREN
    if($mybb->input['action'] == "postgoals_character"){

        if ($activate_marathon == 0 OR $toplist_activate == 0) {
            redirect('index.php', $lang->postinggoal_redirect_toplist);
            return;
        }
        
        add_breadcrumb ($lang->postinggoal_index_toplist_character, "misc.php?action=postgoals_character");

        eval("\$navigation = \"".$templates->get("postinggoal_marathon_toplist_navigation")."\";");

        $topcharacterall_query = $db->query("SELECT uid, username FROM ".TABLE_PREFIX."posts p
        LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
        WHERE p.dateline BETWEEN '".$start_timestamp."' AND '".$end_timestamp."'
        ".$parentlist_sql."
        ".$excludedarea_sql."
        AND p.visible = '1'
        ".$excludedaccounts_sql."
        GROUP BY uid, username
        ");

        $toplist_characters = "";
        $all_posts = [];
        $all_words = [];
        $all_characters = [];
        
        while($topcharacterall = $db->fetch_array($topcharacterall_query)){

            // Leer laufen lassen
            $uid = "";
            $username = "";

            // Mit Infos füllen
            $uid = $topcharacterall['uid'];
            $username = $topcharacterall['username'];

            $topcharacter_query = $db->query("SELECT uid, message, username FROM ".TABLE_PREFIX."posts p
            LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
            WHERE p.dateline BETWEEN '".$start_timestamp."' AND '".$end_timestamp."'
            ".$parentlist_sql."
            ".$excludedarea_sql."
            AND p.visible = '1'
            AND p.uid = '".$uid."'
            AND p.username = '".$username."'
            ".$excludedaccounts_sql."
            ");

            while ($post = $db->fetch_array($topcharacter_query)) {

                if ($uid == 0) {
                    $user = $post['username'];
                } else {
                    $user = $post['uid'];
                }

                if (!isset($all_posts[$user])) {
                    $all_posts[$user] = 0;
                    $all_words[$user] = 0;
                    $all_characters[$user] = 0;
                }

                $all_posts[$user] += 1;
                $all_words[$user] += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
                $all_characters[$user] += strlen($post['message']);
            }
        }
        
        arsort($all_posts);
        
        $counter_limit = 0;
        foreach ($all_posts as $userid => $postcount) {
    
            // Leer laufen lassen
            $charactername = "";
            $name = "";
            $countposts = "";
            $countwords = "";
            $countcharacters = "";
        
            // Mit Infos füllen
            if (is_numeric($userid)) {
                $charactername = get_user($userid)['username'];
                $name = build_profile_link($charactername, $userid);
            } else {
                $charactername = $userid;
                $name = $charactername;
            }
            $countposts = number_format($postcount, '0', ',', '.');
            $countwords = number_format($all_words[$userid], '0', ',', '.');    
            $countcharacters = number_format($all_characters[$userid], '0', ',', '.');
    
            eval("\$toplist_characters .= \"".$templates->get("postinggoal_marathon_toplist_bit")."\";");

            if ($toplist_limit != 0) {
                $counter_limit++;
                if ($counter_limit >= $toplist_limit) {
                    break;
                }
            }
        }

        eval("\$page = \"".$templates->get("postinggoal_marathon_toplist_character")."\";");
        output_page($page);
        die();
    }

    // TOPLISTE NACH SPIELERN
    if($mybb->input['action'] == "postgoals_player"){

        if ($activate_marathon == 0 OR $toplist_activate == 0) {
            redirect('index.php', $lang->postinggoal_redirect_toplist);
            return;
        }
        
        add_breadcrumb ($lang->postinggoal_index_toplist_player, "misc.php?action=postgoals_player");

        eval("\$navigation = \"".$templates->get("postinggoal_marathon_toplist_navigation")."\";");

        $topplayerall_query = $db->query("SELECT uid FROM ".TABLE_PREFIX."posts p
        LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
        WHERE p.dateline BETWEEN '".$start_timestamp."' AND '".$end_timestamp."'
        ".$parentlist_sql."
        ".$excludedarea_sql."
        AND p.visible = '1'
        ".$excludedaccounts_sql."
        AND p.uid != '0'
        GROUP BY uid
        ");

        $toplist_player = "";
        $all_accounts = [];
        while($topplayerall = $db->fetch_array($topplayerall_query)){

            // Leer laufen lassen
            $uid = "";
            $as_uid = "";

            // Mit Infos füllen
            $uid = $topplayerall['uid'];

            $as_uid = $db->fetch_field($db->simple_select("users", "as_uid", "uid = '".$uid."'"), "as_uid");
            if(empty($as_uid)) {
                $as_uid = $uid;
            } else {
                $as_uid = $as_uid;
            }

            $all_accounts[$uid] = $as_uid;
        }

        // Entferne doppelte Einträge aus dem Array
        $all_mainaccounts = array_unique($all_accounts);

        $all_posts = [];
        $all_words = [];
        $all_characters = [];
        foreach ($all_mainaccounts as $mainuid) {

            $all_charas = postinggoal_get_allchars($mainuid);
            $all_charastring = implode(",", array_keys($all_charas));

            $topplayer_query = $db->query("SELECT uid, message FROM ".TABLE_PREFIX."posts p
            LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
            WHERE p.dateline BETWEEN '".$start_timestamp."' AND '".$end_timestamp."'
            ".$parentlist_sql."
            ".$excludedarea_sql."
            AND p.visible = '1'
            AND p.uid IN (".$all_charastring.")
            ".$excludedaccounts_sql."
            ");

            while ($post = $db->fetch_array($topplayer_query)) {

                if (!isset($all_posts[$mainuid])) {
                    $all_posts[$mainuid] = 0;
                    $all_words[$mainuid] = 0;
                    $all_characters[$mainuid] = 0;
                }

                $all_posts[$mainuid] += 1;
                $all_words[$mainuid] += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
                $all_characters[$mainuid] += strlen($post['message']);
            }
        }
        
        arsort($all_posts);
        
        $counter_limit = 0;
        foreach ($all_posts as $userid => $postcount) {
    
            // Leer laufen lassen
            $playername = "";
            $name = "";
            $countposts = "";
            $countwords = "";
            $countcharacters = "";
        
            // Mit Infos füllen
            // wenn Zahl => klassisches Profilfeld
            if (is_numeric($playername_setting)) {
                $playername = $db->fetch_field($db->simple_select("userfields", "fid".$playername_setting, "ufid = '".$userid."'"), "fid".$playername_setting);
                $name = build_profile_link($playername, $userid);
            } else {
                $playerid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
                $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "fieldid = '".$playerid."' AND uid = '".$userid."'"), "value");
                $name = build_profile_link($playername, $userid);
            }
            $countposts = number_format($postcount, '0', ',', '.');
            $countwords = number_format($all_words[$userid], '0', ',', '.');    
            $countcharacters = number_format($all_characters[$userid], '0', ',', '.');
    
            eval("\$toplist_player .= \"".$templates->get("postinggoal_marathon_toplist_bit")."\";");

            if ($toplist_limit != 0) {
                $counter_limit++;
                if ($counter_limit >= $toplist_limit) {
                    break;
                }
            }
        }

        eval("\$page = \"".$templates->get("postinggoal_marathon_toplist_player")."\";");
        output_page($page);
        die();
    }

    // ÜBERSICHT PERSÖNLICHE POST-CHALLENGES - AKTIV
    if($mybb->input['action'] == "postchallenges_overview_active"){

        add_breadcrumb ($lang->postinggoal_overview_nav_active, "misc.php?action=postchallenges_overview_active");

        if ($activate_challenges == 0 OR $overview_activate == 0) {
            redirect('index.php', $lang->postinggoal_redirect_overview);
            return;
        }

        if (!is_member($overview_permissions)) {
            error($lang->postinggoal_overview_permissions);
            return;
        }

        eval("\$navigation = \"".$templates->get("postinggoal_challenges_overview_navigation")."\";");

        $perpage = 10;
        $count_active = $db->num_rows($db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges WHERE enddate > ".$today_time." AND reportstatus = 0"));
        $input_page = $mybb->get_input('page', MyBB::INPUT_INT);
        if($input_page) {
            $start = ($input_page-1) *$perpage;
        }
        else {
            $start = 0;
            $input_page = 1;
        }
        $end = $start + $perpage;
        $lower = $start+1;
        $upper = $end;
        if($upper > $count_active) {
            $upper = $count_active;
        }

        $page_url = htmlspecialchars_uni("misc.php?action=postchallenges_overview_active");

        $multipage = multipage($count_active, $perpage, $input_page, $page_url);

        $multipage_sql = "LIMIT ".$start.", ".$perpage;

        // AKTUELLE CHALLENGE
        $active_challenge = $db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges
        WHERE enddate > ".$today_time."
        AND reportstatus = 0
        ORDER BY enddate ASC
        ".$multipage_sql."
        ");

        $challenges_bit = "";
        $challenges_active_none = $lang->postinggoal_overview_challenge_active_none;
        while ($chell = $db->fetch_array($active_challenge)) {
            $challenges_active_none = "";

            // Leer laufen lassen
            $pgid = "";
            $userid = "";
            $posts = "";
            $words = "";
            $character = "";
            $days = "";
            $enddate_timestamp = "";
            $enddate_date = "";
            $startdate_timestamp = "";
            $startdate_date = "";
            $playername = "";
            $postsgoal = "";
            $wordsgoal = "";
            $charactergoal = "";
            $goal_headline = "";
            $countposts = "";
            $countwords = "";
            $countcharacters = "";
            $status_fazit = "";
            $goal_status = "";

            // Mit Infos füllen
            $pgid = $chell['pgid'];
            $userid = $chell['uid'];
            $posts = $chell['posts'];
            $words = $chell['words'];
            $character = $chell['characters'];
            $days = $chell['days'];
            $enddate_timestamp = $chell['enddate'];
            $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
            $startdate_timestamp = $chell['startdate'];
            $startdate_date = date($mybb->settings['dateformat'], $startdate_timestamp);

            // Spielername
            // wenn Zahl => klassisches Profilfeld
            if (is_numeric($playername_setting)) {
                $playername = $db->fetch_field($db->simple_select("userfields", "fid".$playername_setting, "ufid = '".$userid."'"), "fid".$playername_setting);
            } else {
                $playerid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
                $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "fieldid = '".$playerid."' AND uid = '".$userid."'"), "value");
            }

            // hübsche Zahlen
            $postsgoal = number_format($posts, '0', ',', '.');
            $wordsgoal = number_format($words, '0', ',', '.');
            $charactergoal = number_format($character, '0', ',', '.');

            // Restliche Tage
            $intvl = $today_date->diff(new DateTime(date("Y-m-d", $enddate_timestamp)));
            $remaining_days = $intvl->days;

            // Gesamtziel - Headline
            if ($words != 0 AND $character != 0) {
                $goal_headline = $lang->sprintf($lang->postinggoal_overview_active_goal_full, $postsgoal, $days, $remaining_days, $wordsgoal, $charactergoal);
            } else {
                if ($words != 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_overview_active_goal_words, $postsgoal, $days, $remaining_days, $wordsgoal);
                } else if ($words == 0 AND $character != 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_overview_active_goal_characters, $postsgoal, $days, $remaining_days, $charactergoal);
                } else if ($words == 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_overview_active_goal_posts, $postsgoal, $days, $remaining_days);
                }
            }

            // USER-ID
            $activeUser_allcharas = postinggoal_get_allchars($userid);
            $activeUser_charastring = implode(",", array_keys($activeUser_allcharas));

            // ZÄHLEN DER POSTS
            $post_query = $db->query("SELECT message FROM ".TABLE_PREFIX."posts p
            LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
            WHERE p.dateline BETWEEN '".$startdate_timestamp."' AND '".$enddate_timestamp."'
            ".$parentlist_sql."
            ".$excludedarea_sql."
            AND p.uid IN (".$activeUser_charastring.")
            AND p.visible = '1'
            ");
        
            $postscount = $wordscount = $characterscount = 0;
            while ($post = $db->fetch_array($post_query)) {
                // Post
                $postscount++;
                // Wörter
                $wordscount += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
                // Zeichen
                $characterscount += strlen($post['message']);
            }

            // hübsche Zahlen
            $countposts = number_format($postscount, '0', ',', '.');
            $countwords = number_format($wordscount, '0', ',', '.');
            $countcharacters = number_format($characterscount, '0', ',', '.');

            $status_result = $lang->sprintf($lang->postinggoal_overview_result, $countposts, $countcharacters, $countwords);

            // Erreicht
            if ($postscount >= $posts AND $wordscount >= $words AND $characterscount >= $character) {
                $goal_status = $lang->postinggoal_overview_reached;
            } else {
                $goal_status = $lang->postinggoal_overview_notreached_active;
            }

            eval("\$challenges_bit .= \"".$templates->get("postinggoal_challenges_overview_bit")."\";");
        }

        eval("\$page = \"".$templates->get("postinggoal_challenges_overview_active")."\";");
        output_page($page);
        die();
    }

    // ÜBERSICHT PERSÖNLICHE POST-CHALLENGES - VERGANGEN
    if($mybb->input['action'] == "postchallenges_overview_finished"){

        add_breadcrumb ($lang->postinggoal_overview_nav_finished, "misc.php?action=postchallenges_overview_finished");

        if ($activate_challenges == 0 OR $overview_activate == 0) {
            redirect('index.php', $lang->postinggoal_redirect_overview);
            return;
        }

        if (!is_member($overview_permissions)) {
            error($lang->postinggoal_overview_permissions);
            return;
        }

        eval("\$navigation = \"".$templates->get("postinggoal_challenges_overview_navigation")."\";");

        $perpage = 10;
        $count_finished = $db->num_rows($db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges WHERE enddate < ".$today_time." OR reportstatus = 1"));
        $input_page = $mybb->get_input('page', MyBB::INPUT_INT);
        if($input_page) {
            $start = ($input_page-1) *$perpage;
        }
        else {
            $start = 0;
            $input_page = 1;
        }
        $end = $start + $perpage;
        $lower = $start+1;
        $upper = $end;
        if($upper > $count_finished) {
            $upper = $count_finished;
        }

        $page_url = htmlspecialchars_uni("misc.php?action=postchallenges_overview_finished");

        $multipage = multipage($count_finished, $perpage, $input_page, $page_url);

        $multipage_sql = "LIMIT ".$start.", ".$perpage;

        // AKTUELLE CHALLENGE
        $finished_challenge = $db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges
        WHERE enddate < ".$today_time."
        AND enddate < ".$today_time." OR reportstatus = 1
        ORDER BY enddate DESC
        ".$multipage_sql."
        ");

        $challenges_bit = "";
        $challenges_finished_none = $lang->postinggoal_overview_challenge_finished_none;
        while ($chell = $db->fetch_array($finished_challenge)) {
            $challenges_finished_none = "";

            // Leer laufen lassen
            $pgid = "";
            $userid = "";
            $posts = "";
            $words = "";
            $character = "";
            $days = "";
            $enddate_timestamp = "";
            $enddate_date = "";
            $startdate_timestamp = "";
            $startdate_date = "";
            $playername = "";
            $postsgoal = "";
            $wordsgoal = "";
            $charactergoal = "";
            $goal_headline = "";
            $countposts = "";
            $countwords = "";
            $countcharacters = "";
            $status_fazit = "";
            $goal_status = "";

            // Mit Infos füllen
            $pgid = $chell['pgid'];
            $userid = $chell['uid'];
            $posts = $chell['posts'];
            $words = $chell['words'];
            $character = $chell['characters'];
            $days = $chell['days'];
            $reportstatus = $chell['reportstatus'];
            $startdate_timestamp = $chell['startdate'];
            $startdate_date = date($mybb->settings['dateformat'], $startdate_timestamp);
            if ($reportstatus == 1) {
                $original_enddate = strtotime("+".$days." day", $startdate_timestamp);
                list($year_original, $month_original, $day_original) = explode('-', date('Y-m-d', $original_enddate));
                $original_enddate = mktime(0, 0, 0, $month_original, $day_original, $year_original);
                $report_enddate = $chell['enddate'];

                // Die beiden Daten sind gleich
                if ($original_enddate == $report_enddate) {
                    $enddate_timestamp = $chell['enddate'];
                    $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
                    $premature_days_lang = "";
                }
                // Die beiden Daten sind nicht gleich
                else {
                    $enddate_timestamp = $original_enddate;
                    $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
                    $enddate_report = date($mybb->settings['dateformat'], $report_enddate);

                    // wie viel früher beendet?
                    $original_date = new DateTime(date("Y-m-d", $original_enddate));
                    $intvl = $original_date->diff(new DateTime(date("Y-m-d", $report_enddate)));
                    $premature_days = $intvl->days;

                    if ($premature_days > 1) {
                        $premature_days_lang = $lang->sprintf($lang->postinggoal_overview_finished_prematuredays, $premature_days, "Tage");
                    } else {
                        $premature_days_lang = $lang->sprintf($lang->postinggoal_overview_finished_prematuredays, $premature_days, "Tag");
                    }
                }
            } else {
                $enddate_timestamp = $chell['enddate'];
                $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
                $premature_days_lang = "";
            }

            // Spielername
            // wenn Zahl => klassisches Profilfeld
            if (is_numeric($playername_setting)) {
                $playername = $db->fetch_field($db->simple_select("userfields", "fid".$playername_setting, "ufid = '".$userid."'"), "fid".$playername_setting);
            } else {
                $playerid = $db->fetch_field($db->simple_select("application_ucp_fields", "id", "fieldname = '".$playername_setting."'"), "id");
                $playername = $db->fetch_field($db->simple_select("application_ucp_userfields", "value", "fieldid = '".$playerid."' AND uid = '".$userid."'"), "value");
            }

            // hübsche Zahlen
            $postsgoal = number_format($posts, '0', ',', '.');
            $wordsgoal = number_format($words, '0', ',', '.');
            $charactergoal = number_format($character, '0', ',', '.');

            // Gesamtziel - Headline
            if ($words != 0 AND $character != 0) {
                $goal_headline = $lang->sprintf($lang->postinggoal_overview_finished_goal_full, $postsgoal, $days, $wordsgoal, $charactergoal, $startdate_date, $enddate_date, $premature_days_lang);
            } else {
                if ($words != 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_overview_finished_goal_words, $postsgoal, $days, $wordsgoal, $startdate_date, $enddate_date, $premature_days_lang);
                } else if ($words == 0 AND $character != 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_overview_finished_goal_characters, $postsgoal, $days, $charactergoal, $startdate_date, $enddate_date, $premature_days_lang);
                } else if ($words == 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_overview_finished_goal_posts, $postsgoal, $days, $startdate_date, $enddate_date, $premature_days_lang);
                }
            }

            // USER-ID
            $activeUser_allcharas = postinggoal_get_allchars($userid);
            $activeUser_charastring = implode(",", array_keys($activeUser_allcharas));

            // ZÄHLEN DER POSTS
            $post_query = $db->query("SELECT message FROM ".TABLE_PREFIX."posts p
            LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
            WHERE p.dateline BETWEEN '".$startdate_timestamp."' AND '".$enddate_timestamp."'
            ".$parentlist_sql."
            ".$excludedarea_sql."
            AND p.uid IN (".$activeUser_charastring.")
            AND p.visible = '1'
            ");
        
            $postscount = $wordscount = $characterscount = 0;
            while ($post = $db->fetch_array($post_query)) {
                // Post
                $postscount++;
                // Wörter
                $wordscount += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
                // Zeichen
                $characterscount += strlen($post['message']);
            }

            // hübsche Zahlen
            $countposts = number_format($postscount, '0', ',', '.');
            $countwords = number_format($wordscount, '0', ',', '.');
            $countcharacters = number_format($characterscount, '0', ',', '.');

            $status_result = $lang->sprintf($lang->postinggoal_overview_result, $countposts, $countcharacters, $countwords);

            // Erreicht
            if ($postscount >= $posts AND $wordscount >= $words AND $characterscount >= $character) {
                $goal_status = $lang->postinggoal_overview_reached;
            } else {
                $goal_status = $lang->postinggoal_overview_notreached_finished;
            }

            eval("\$challenges_bit .= \"".$templates->get("postinggoal_challenges_overview_bit")."\";");
        }

        eval("\$page = \"".$templates->get("postinggoal_challenges_overview_finished")."\";");
        output_page($page);
        die();
    }

}

// USERCP => POST-CHALLENGES
// Anzeige Usercp-Menu
function postinggoal_usercpmenu() {
	global $mybb, $templates, $lang, $usercpmenu, $collapsed, $collapsedimg;

    $activate_challenges = $mybb->settings['postinggoal_challenges_activate'];

    // zurück, wenn es nicht aktiv ist
    if ($activate_challenges == 0) return;

	// SPRACHDATEI LADEN
	$lang->load("postinggoal");

	eval("\$usercpmenu .= \"".$templates->get("postinggoal_challenges_usercp_nav")."\";");
}

// Post-Challenges im Usercp
function postinggoal_usercp() {

	global $mybb, $db, $plugins, $page, $templates, $theme, $lang, $header, $headerinclude, $footer, $usercpnav, $disable_myalerts_hook;

    // EINSTELLUNGEN
    $activate_challenges = $mybb->settings['postinggoal_challenges_activate'];
    $inplayarea = $mybb->settings['postinggoal_inplayarea'];
    $selectedforums = explode(",", $inplayarea);
	$excludedarea = $mybb->settings['postinggoal_excludedarea'];
    $finishedchallenge_tid = $mybb->settings['postinggoal_challenges_thread'];

    if(!empty($excludedarea)) {
        $excludedarea_sql = "AND p.fid NOT IN (".$excludedarea.")";
    } else {
        $excludedarea_sql = "";
    }

    // FID-ARRAY BILDEN
    $parentlist_sql = "AND (";
    foreach ($selectedforums as $selected) {
        $parentlist_sql .= "(concat(',',f.parentlist,',') LIKE '%,".$selected.",%') OR ";
    }
    $parentlist_sql = substr($parentlist_sql, 0, -4).")";

    // Heute
    $today_time = time();
    $today_date  = new DateTime(date("Y-m-d", time()));

	// SPRACHDATEI LADEN
	$lang->load("postinggoal");

	// DAS ACTION MENÜ
	$mybb->input['action'] = $mybb->get_input('action');

	// USER-ID
	$user_id = $mybb->user['uid'];
    $allcharas = postinggoal_get_allchars($user_id);
    $charastring = implode(",", array_keys($allcharas));
    // nach Username sortieren 
    asort($allcharas);

	if ($mybb->input['action'] == "postchallenges") {

		if ($activate_challenges != 1) {
			redirect('usercp.php', $lang->postinggoal_redirect_usercp);
		}

		add_breadcrumb($lang->nav_usercp, "usercp.php");
        add_breadcrumb($lang->postinggoal_usercp_nav, "usercp.php?action=postchallenges");

        // AKTUELLE CHALLENGE
        $active_challenge = $db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges
        WHERE uid IN (".$charastring.")
        AND enddate > ".$today_time."
        AND reportstatus = 0
        ");

        $challenge_active = $lang->postinggoal_usercp_challenge_active_none;
        eval("\$challenge_add = \"".$templates->get("postinggoal_challenges_usercp_add")."\";");
        while ($chell = $db->fetch_array($active_challenge)) {
            $challenge_add = "";

            // Leer laufen lassen
            $pgid = "";
            $uid = "";
            $posts = "";
            $words = "";
            $character = "";
            $days = "";
            $enddate = "";
            $startdate = "";

            // Mit Infos füllen
            $pgid = $chell['pgid'];
            $uid = $chell['uid'];
            $posts = $chell['posts'];
            $words = $chell['words'];
            $character = $chell['characters'];
            $days = $chell['days'];
            $enddate_timestamp = $chell['enddate'];
            $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
            $startdate_timestamp = $chell['startdate'];
            $startdate_date = date($mybb->settings['dateformat'], $startdate_timestamp);

            // hübsche Zahlen
            $postsgoal = number_format($posts, '0', ',', '.');
            $wordsgoal = number_format($words, '0', ',', '.');
            $charactergoal = number_format($character, '0', ',', '.');

            // Gesamtziel - Headline
            if ($words != 0 AND $character != 0) {
                $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_active_goal_full, $postsgoal, $wordsgoal, $charactergoal, $days, $startdate_date, $enddate_date);
            } else {
                if ($words != 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_active_goal_words, $postsgoal, $wordsgoal, $days, $startdate_date, $enddate_date);
                } else if ($words == 0 AND $character != 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_active_goal_characters, $postsgoal, $charactergoal, $days, $startdate_date, $enddate_date);
                } else if ($words == 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_active_goal_posts, $postsgoal, $days, $startdate_date, $enddate_date);
                }
            }

            // Postziel
            $post_challenge = $lang->sprintf($lang->postinggoal_usercp_challenge_active_count_posts_goal, $postsgoal);
            // Wörterziel
            if ($words != 0) {
                $word_challenge = $lang->sprintf($lang->postinggoal_usercp_challenge_active_count_words_goal, $wordsgoal);
            } else {
                $word_challenge = $lang->postinggoal_usercp_challenge_active_count_words;
            }
            // Zeichenziel
            if ($character != 0) {
                $character_challenge = $lang->sprintf($lang->postinggoal_usercp_challenge_active_count_characters_goal, $charactergoal);
            } else {
                $character_challenge = $lang->postinggoal_usercp_challenge_active_count_characters;
            }

            // ZÄHLEN DER POSTS
            $post_query = $db->query("SELECT message FROM ".TABLE_PREFIX."posts p
            LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
            WHERE p.dateline BETWEEN '".$startdate_timestamp."' AND '".$enddate_timestamp."'
            ".$parentlist_sql."
            ".$excludedarea_sql."
            AND p.uid IN (".$charastring.")
            AND p.visible = '1'
            ");
        
            $postscount = $wordscount = $characterscount = 0;
            while ($post = $db->fetch_array($post_query)) {
                // Post
                $postscount++;
                // Wörter
                $wordscount += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
                // Zeichen
                $characterscount += strlen($post['message']);
            }

            // hübsche Zahlen
            $countposts = number_format($postscount, '0', ',', '.');
            $countwords = number_format($wordscount, '0', ',', '.');
            $countcharacters = number_format($characterscount, '0', ',', '.');

            // Erreicht
            if ($postscount >= $posts AND $wordscount >= $words AND $characterscount >= $character) {
                $intvl = $today_date->diff(new DateTime(date("Y-m-d", $enddate_timestamp)));
                $remaining_days = $intvl->days;
                $goal_status = $lang->sprintf($lang->postinggoal_usercp_challenge_active_goalstatus_reached, $remaining_days);

                if ($finishedchallenge_tid > 0) {
                    $input_array = array(
                        "countposts" => $countposts,
                        "countwords" => $countwords,
                        "countcharacters" => $countcharacters,
                        "postsgoal" => $postsgoal,
                        "wordsgoal" => $wordsgoal,
                        "charactergoal" => $charactergoal,
                        "days" => $days,
                        "pgid" => $pgid,
                        "username" => $mybb->user['username'],
                        "uid" => $mybb->user['uid'],
                        "my_post_key" => $mybb->post_code,
                        "enddate" => $enddate_timestamp,
                    );
                    $challenge_inputs = "";
                    foreach($input_array as $name => $value){
                       $challenge_inputs .= "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\" />";
                    }

                    $button_value = $lang->postinggoal_usercp_challenge_active_report_button;
                    $button_onClick = "onClick=\"return confirm('".$lang->postinggoal_usercp_challenge_active_report_button_onClick."')\"";

                    eval("\$challenge_report = \"".$templates->get("postinggoal_challenges_usercp_report")."\";");
                } else {
                    $challenge_report = "";
                }
            } else {
                $challenge_report = "";
                $intvl = $today_date->diff(new DateTime(date("Y-m-d", $enddate_timestamp)));
                $remaining_days = $intvl->days;
                $goal_status = $lang->sprintf($lang->postinggoal_usercp_challenge_active_goalstatus_notreached, $remaining_days);
            }

            // CHARAKTER STATISTIK
            $characters_bit = "";
            foreach ($allcharas as $charaid => $charactername) {

                // leer laufen 
                $countposts_chara = "";
                $countwords_chara = "";
                $countcharacters_chara = "";
                $charactername_formated = "";
                $charactername_formated_link = "";
                $charactername_link = "";
                $charactername_fullname = "";
                $charactername_first = "";
                $charactername_last = "";

                // Charakternamen
                // mit Gruppenfarbe
                $charactername_formated = format_name($charactername, get_user($charaid)['usergroup'], get_user($charaid)['displaygroup']);
                $charactername_formated_link = build_profile_link(format_name($charactername, get_user($charaid)['usergroup'], get_user($charaid)['displaygroup']), $charaid);	
                // Nur Link
                $charactername_link = build_profile_link($charactername, $charaid);
                // Name gesplittet
                $charactername_fullname = explode(" ", $charactername);
                $charactername_first = array_shift($charactername_fullname);
                $charactername_last = implode(" ", $charactername_fullname);

                $postchara_query = $db->query("SELECT message FROM ".TABLE_PREFIX."posts p
                LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
                WHERE p.dateline BETWEEN '".$startdate_timestamp."' AND '".$enddate_timestamp."'
                ".$parentlist_sql."
                ".$excludedarea_sql."
                AND p.uid = '".$charaid."'
                AND p.visible = '1'
                ");
        
                $postscount_chara = $wordscount_chara = $characterscount_chara = 0;
                while ($post_chara = $db->fetch_array($postchara_query)) {
                    // Post
                    $postscount_chara++;
                    // Wörter
                    $wordscount_chara += count(preg_split('~[^\p{L}\p{N}\']+~u', $post_chara['message']));
                    // Zeichen
                    $characterscount_chara += strlen($post_chara['message']);
                }
    
                // hübsche Zahlen
                $countposts_chara = number_format($postscount_chara, '0', ',', '.');
                $countwords_chara = number_format($wordscount_chara, '0', ',', '.');
                $countcharacters_chara = number_format($characterscount_chara, '0', ',', '.');

                eval("\$characters_bit .= \"".$templates->get("postinggoal_challenges_usercp_characters")."\";");
            }

            eval("\$challenge_active = \"".$templates->get("postinggoal_challenges_usercp_active")."\";");
        }

        // VERGANGENE CHALLENGES
        $perpage = 10;
        $count_challenges = $db->num_rows($db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges
        WHERE uid IN (".$charastring.")
        AND enddate < ".$today_time.""));
        $input_page = $mybb->get_input('page', MyBB::INPUT_INT);
        if($input_page) {
            $start = ($input_page-1) *$perpage;
        }
        else {
            $start = 0;
            $input_page = 1;
        }
        $end = $start + $perpage;
        $lower = $start+1;
        $upper = $end;
        if($upper > $count_challenges) {
            $upper = $count_challenges;
        }

        $page_url = htmlspecialchars_uni("usercp.php?action=postchallenges");

        $multipage = multipage($count_challenges, $perpage, $input_page, $page_url);

        $multipage_sql = "LIMIT ".$start.", ".$perpage;

        $finished_challenge = $db->query("SELECT * FROM ".TABLE_PREFIX."user_postchallenges
        WHERE uid IN (".$charastring.")
        AND enddate < ".$today_time."
        ORDER BY pgid DESC
        ".$multipage_sql."
        ");

        $challenge_finished = "";
        $challenge_finished_none = $lang->postinggoal_usercp_challenge_finished_none;
        while ($chell = $db->fetch_array($finished_challenge)) {
            $challenge_finished_none = "";

            // Leer laufen lassen
            $pgid = "";
            $uid = "";
            $posts = "";
            $words = "";
            $character = "";
            $days = "";
            $enddate = "";
            $startdate = "";
            $postsgoal = "";
            $wordsgoal = "";
            $charactergoal = "";
            $goal_headline = "";
            $countposts = "";
            $countwords = "";
            $countcharacters = "";
            $status_result = "";
            $goal_status = "";
            $reportstatus = "";

            // Mit Infos füllen
            $pgid = $chell['pgid'];
            $uid = $chell['uid'];
            $posts = $chell['posts'];
            $words = $chell['words'];
            $character = $chell['characters'];
            $days = $chell['days'];
            $reportstatus = $chell['reportstatus'];
            $startdate_timestamp = $chell['startdate'];
            $startdate_date = date($mybb->settings['dateformat'], $startdate_timestamp);

            // Enddatum rausfinden
            if ($reportstatus == 1) {
                $original_enddate = strtotime("+".$days." day", $startdate_timestamp);
                list($year_original, $month_original, $day_original) = explode('-', date('Y-m-d', $original_enddate));
                $original_enddate = mktime(0, 0, 0, $month_original, $day_original, $year_original);
                $report_enddate = $chell['enddate'];

                // Die beiden Daten sind gleich
                if ($original_enddate == $report_enddate) {
                    $enddate_timestamp = $chell['enddate'];
                    $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
                    $premature_days_lang = "";
                }
                // Die beiden Daten sind nicht gleich
                else {
                    $enddate_timestamp = $original_enddate;
                    $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
                    $enddate_report = date($mybb->settings['dateformat'], $report_enddate);

                    // wie viel früher beendet?
                    $original_date = new DateTime(date("Y-m-d", $original_enddate));
                    $intvl = $original_date->diff(new DateTime(date("Y-m-d", $report_enddate)));
                    $premature_days = $intvl->days;

                    if ($premature_days > 1) {
                        $premature_days_lang = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_prematuredays, $premature_days, "Tage", $enddate_report);
                    } else {
                        $premature_days_lang = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_prematuredays, $premature_days, "Tag", $enddate_report);
                    }
                }
            } else {
                $enddate_timestamp = $chell['enddate'];
                $enddate_date = date($mybb->settings['dateformat'], $enddate_timestamp);
                $premature_days_lang = "";
            }

            // hübsche Zahlen
            $postsgoal = number_format($posts, '0', ',', '.');
            $wordsgoal = number_format($words, '0', ',', '.');
            $charactergoal = number_format($character, '0', ',', '.');

            // Gesamtziel - Headline
            if ($words != 0 AND $character != 0) {
                $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_goal_full, $postsgoal, $wordsgoal, $charactergoal, $days, $startdate_date, $enddate_date, $premature_days_lang);
            } else {
                if ($words != 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_goal_words, $postsgoal, $wordsgoal, $days, $startdate_date, $enddate_date, $premature_days_lang);
                } else if ($words == 0 AND $character != 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_goal_characters, $postsgoal, $charactergoal, $days, $startdate_date, $enddate_date, $premature_days_lang);
                } else if ($words == 0 AND $character == 0) {
                    $goal_headline = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_goal_posts, $postsgoal, $days, $startdate_date, $enddate_date,$premature_days_lang);
                }
            }

            // ZÄHLEN DER POSTS
            $post_query = $db->query("SELECT message FROM ".TABLE_PREFIX."posts p
            LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = p.fid)
            WHERE p.dateline BETWEEN '".$startdate_timestamp."' AND '".$enddate_timestamp."'
            ".$parentlist_sql."
            ".$excludedarea_sql."
            AND p.uid IN (".$charastring.")
            AND p.visible = '1'
            ");
        
            $postscount = $wordscount = $characterscount = 0;
            while ($post = $db->fetch_array($post_query)) {
                // Post
                $postscount++;
                // Wörter
                $wordscount += count(preg_split('~[^\p{L}\p{N}\']+~u', $post['message']));
                // Zeichen
                $characterscount += strlen($post['message']);
            }

            // hübsche Zahlen
            $countposts = number_format($postscount, '0', ',', '.');
            $countwords = number_format($wordscount, '0', ',', '.');
            $countcharacters = number_format($characterscount, '0', ',', '.');

            $status_result = $lang->sprintf($lang->postinggoal_usercp_challenge_finished_result, $countposts, $countcharacters, $countwords);

            // Erreicht
            if ($postscount >= $posts AND $wordscount >= $words AND $characterscount >= $character) {
                $goal_status = $lang->postinggoal_usercp_challenge_finished_goalstatus_reached;

                if ($finishedchallenge_tid > 0 AND $reportstatus != 1) {
                    $input_array = array(
                        "countposts" => $countposts,
                        "countwords" => $countwords,
                        "countcharacters" => $countcharacters,
                        "postsgoal" => $postsgoal,
                        "wordsgoal" => $wordsgoal,
                        "charactergoal" => $charactergoal,
                        "days" => $days,
                        "pgid" => $pgid,
                        "username" => $mybb->user['username'],
                        "uid" => $mybb->user['uid'],
                        "my_post_key" => $mybb->post_code,
                        "enddate" => $enddate_timestamp,
                    );
                    $challenge_inputs = "";
                    foreach($input_array as $name => $value){
                       $challenge_inputs .= "<input type=\"hidden\" name=\"".$name."\" value=\"".$value."\" />";
                    }

                    $button_onClick = "";
                    $button_value = $lang->postinggoal_usercp_challenge_finished_report_button;

                    eval("\$challenge_report = \"".$templates->get("postinggoal_challenges_usercp_report")."\";");
                } else {
                    $challenge_report = "";
                }
            } else {
                $goal_status = $lang->postinggoal_usercp_challenge_finished_goalstatus_notreached;
                $challenge_report = "";
            }

            eval("\$challenge_finished .= \"".$templates->get("postinggoal_challenges_usercp_finished")."\";");
        }
 
        // TEMPLATE FÜR DIE SEITE
        eval("\$page = \"".$templates->get("postinggoal_challenges_usercp")."\";");
        output_page($page);
        die();
    }

	if ($mybb->input['action'] == "postinggoal_addchallenge") {

        $days = (int)$mybb->get_input('enddate');
        // Zeitkomponenten von $today_time auf 23:59:59 => ganzer Tag
        list($year_end, $month_end, $day_end) = explode('-', date('Y-m-d', $today_time));
        $endate = strtotime("+".$days." day", mktime(23, 59, 59, $month_end, $day_end, $year_end));
        // Zeitkomponenten von $today_time auf 00:00:00 => ganzer Tag
        list($year_start, $month_start, $day_start) = explode('-', date('Y-m-d', $today_time));
        $startdate = mktime(0, 0, 0, $month_start, $day_start, $year_start);

        $new_challenge = array(
            'uid' => (int)$user_id,
            'posts' => (int)$mybb->get_input('postgoal'),
            'words' => (int)$mybb->get_input('wortgoal'),
            'characters' => (int)$mybb->get_input('chargoal'),
            'days' => (int)$mybb->get_input('enddate'),
            'startdate' => (int)$startdate,
            'enddate' => (int)$endate
        );

        $db->insert_query("user_postchallenges", $new_challenge);	

        redirect("usercp.php?action=postchallenges", $lang->postinggoal_redirect_add);	
    }

    if ($mybb->input['action'] == "postinggoal_challenge_ready") {

        // Set up posthandler.
        require_once "./global.php";
        require_once MYBB_ROOT."inc/datahandlers/post.php";
        $posthandler = new PostDataHandler("insert");
        $posthandler->action = "newreplay";
        
        // Deaktiviere die MyAlerts-Funktionalität
        if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
            require_once MYBB_ROOT."inc/class_plugins.php";
            $plugins->remove_hook('datahandler_post_insert_post', 'myalertsrow_subscribed');
        }

        // Create session for this user
        require_once MYBB_ROOT.'inc/class_session.php';
        $session = new session;
        $session->init();
        $mybb->session = &$session;

        $tid = $finishedchallenge_tid;
        $thread = get_thread($tid);
        $fid = $thread['fid'];
    
        // Verify incoming POST request
        verify_post_check($mybb->get_input('my_post_key'));

        $uid = $mybb->get_input('uid');
		$username = $mybb->get_input('username');
		$pgid = $mybb->get_input('pgid');
        $days = $mybb->get_input('days');
        $charactergoal = $mybb->get_input('charactergoal');
        $wordsgoal = $mybb->get_input('wordsgoal');
        $postsgoal = $mybb->get_input('postsgoal');
        $countcharacters = $mybb->get_input('countcharacters');
        $countwords = $mybb->get_input('countwords');
        $countposts = $mybb->get_input('countposts');
        $original_enddate = $mybb->get_input('enddate');

        // Datum Kram
        if (date('Y-m-d', $original_enddate) == date('Y-m-d', $today_time)) {
            $enddate = $original_enddate;
            $message_prematuredays = "";
        } else {
            if (date('Y-m-d', $original_enddate) < date('Y-m-d', $today_time)) {
                $enddate = $original_enddate;
                $message_prematuredays = "";
            } else {
                $enddate = $today_time;

                // wie viel früher beendet?
                $original_date = new DateTime(date("Y-m-d", $original_enddate));
                $intvl = $original_date->diff(new DateTime(date("Y-m-d", $enddate)));
                $premature_days = $intvl->days;

                if ($premature_days > 1) {
                    $message_prematuredays = $lang->sprintf($lang->postinggoal_usercp_challenge_report_message_prematuredays_plural, $premature_days);
                } else {
                    $message_prematuredays = $lang->postinggoal_usercp_challenge_report_message_prematuredays_singular;
                }
            }
        }

        // Post zusammenbauen
        if ($wordsgoal != 0 AND $charactergoal != 0) {
            $message_additive = $lang->sprintf($lang->postinggoal_usercp_challenge_report_message_additive_full, $wordsgoal, $charactergoal);
        } else {
            if ($wordsgoal != 0 AND $charactergoal == 0) {
                $message_additive = $lang->sprintf($lang->postinggoal_usercp_challenge_report_message_additive_words, $wordsgoal);
            } else if ($wordsgoal == 0 AND $charactergoal != 0) {
                $message_additive = $lang->sprintf($lang->postinggoal_usercp_challenge_report_message_additive_characters, $charactergoal);
            } else if ($wordsgoal == 0 AND $charactergoal == 0) {
                $message_additive = "<br>";
            }
        }
        $message = $lang->sprintf($lang->postinggoal_usercp_challenge_report_message, $postsgoal, $days, $message_additive, $countposts, $countwords, $countcharacters, $message_prematuredays);

        // Set the post data that came from the input to the $post array.
        $post = array(
            "tid" => $tid,
            "replyto" => 0,
            "fid" => "{$fid}",
            "subject" => "RE: ".$thread['subject'],
            "icon" => -1,
            "uid" => $uid,
            "username" => $username,
            "message" => $message,
            "ipaddress" => $session->packedip,
            "posthash" => $mybb->get_input('posthash')
        );

        if(isset($mybb->input['pid'])){
            $post['pid'] = $mybb->get_input('pid', MyBB::INPUT_INT);
        }

        // Are we saving a draft post?
        $post['savedraft'] = 0;

        // Set up the post options from the input.
        $post['options'] = array(
            "signature" => 1,
            "subscriptionmethod" => "",
            "disablesmilies" => 0	
        );

        // Apply moderation options if we have them
        $post['modoptions'] = $mybb->get_input('modoptions', MyBB::INPUT_ARRAY);	
        $posthandler->set_data($post);

        // Now let the post handler do all the hard work.	
        $valid_post = $posthandler->validate_post();

        $post_errors = array();
        // Fetch friendly error messages if this is an invalid post
        if(!$valid_post){
            $post_errors = $posthandler->get_friendly_errors();
        }
        // $post_errors = inline_error($post_errors);

        // Mark thread as read
        require_once MYBB_ROOT."inc/functions_indicators.php";
        mark_thread_read($tid, $fid);

        $json_data = '';
    
        // Check captcha image
        if($mybb->settings['captchaimage'] && !$uid)
        {
            require_once MYBB_ROOT.'inc/class_captcha.php';
            $post_captcha = new captcha(false, "post_captcha");
    
            if($post_captcha->validate_captcha() == false)
            {
                // CAPTCHA validation failed
                foreach($post_captcha->get_errors() as $error)
                {
                    $post_errors[] = $error;
                }
            }
            else
            {
                $hide_captcha = true;
            }
    
            if($mybb->get_input('ajax', MyBB::INPUT_INT) && $post_captcha->type == 1)
            {
                $randomstr = random_str(5);
                $imagehash = md5(random_str(12));
    
                $imagearray = array(
                    "imagehash" => $imagehash,
                    "imagestring" => $randomstr,
                    "dateline" => TIME_NOW
                );
    
                $db->insert_query("captcha", $imagearray);
    
                //header("Content-type: text/html; charset={$lang->settings['charset']}");
                $data = '';
                $data .= "<captcha>$imagehash";
    
                if($hide_captcha)
                {
                    $data .= "|$randomstr";
                }
    
                $data .= "</captcha>";
    
                //header("Content-type: application/json; charset={$lang->settings['charset']}");
                $json_data = array("data" => $data);
            }
        }

        // One or more errors returned, fetch error list and throw to newreply page
        if(count($post_errors) > 0)
        {
            $reply_errors = inline_error($post_errors, '', $json_data);
            $mybb->input['action'] = "newreply";
        }
        else
        {
            $postinfo = $posthandler->insert_post();
            $pid = $postinfo['pid'];
            $visible = $postinfo['visible'];
    
            if(isset($postinfo['closed']))
            {
                $closed = $postinfo['closed'];
            }
            else
            {
                $closed = '';
            }
    
            // Invalidate solved captcha
            if($mybb->settings['captchaimage'] && !$uid)
            {
                $post_captcha->invalidate_captcha();
            }
    
            $force_redirect = false;

            // Challenge gemeldet
            $report_challenge = [
                "reportstatus" => (int)1,
                "enddate" => (int)$enddate,
            ];
    
            $db->update_query("user_postchallenges", $report_challenge, "pgid = '".$pgid."'");
    
            // Visible post
            $url = get_post_link($pid, $tid)."#pid{$pid}";
            redirect($url, $lang->postinggoal_redirect_report, "", $force_redirect);
            exit;
        }
    }
}

// ONLINE LOCATION
function postinggoal_online_activity($user_activity) {

	global $parameters, $user;

	$split_loc = explode(".php", $user_activity['location']);
	if(isset($user['location']) && $split_loc[0] == $user['location']) { 
		$filename = '';
	} else {
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
	}

	switch ($filename) {
		case 'misc':
			if ($parameters['action'] == "postgoals_character") {
				$user_activity['activity'] = "postgoals_character";
			}
			if ($parameters['action'] == "postgoals_player") {
				$user_activity['activity'] = "postgoals_player";
			}
			if ($parameters['action'] == "postchallenges_overview_active") {
				$user_activity['activity'] = "postchallenges_overview_active";
			}
			if ($parameters['action'] == "postchallenges_overview_finished") {
				$user_activity['activity'] = "postchallenges_overview_finished";
			}
            break;
        case 'usercp':
            if ($parameters['action'] == "postchallenges") {
                $user_activity['activity'] = "postgoals_challenges";
            }
            break;    
	}

	return $user_activity;
}
function postinggoal_online_location($plugin_array) {

	global $mybb, $theme, $lang, $db;
    
    // SPRACHDATEI LADEN
    $lang->load("postinggoal");

	if ($plugin_array['user_activity']['activity'] == "postgoals_character") {
		$plugin_array['location_name'] = $lang->postgoals_online_location_toplist_character;
	}

	if ($plugin_array['user_activity']['activity'] == "postgoals_player") {
		$plugin_array['location_name'] = $lang->postgoals_online_location_toplist_player;
	}

	if ($plugin_array['user_activity']['activity'] == "postchallenges_overview_active") {
		$plugin_array['location_name'] = $lang->postgoals_online_location_overview_active;
	}

	if ($plugin_array['user_activity']['activity'] == "postchallenges_overview_finished") {
		$plugin_array['location_name'] = $lang->postgoals_online_location_overview_finished;
	}

	if ($plugin_array['user_activity']['activity'] == "postgoals_challenges") {
		$plugin_array['location_name'] = $lang->postgoals_online_location_usercp;
	}

	return $plugin_array;
}

// ACCOUNTSWITCHER HILFSFUNKTION
function postinggoal_get_allchars($uid) {
	global $db, $cache, $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

	//für den fall nicht mit hauptaccount online
	if (isset($mybb->user['as_uid'])) {
        $as_uid = intval($mybb->user['as_uid']);
    } else {
        $as_uid = 0;
    }

	$charas = array();
	if ($as_uid == 0) {
	  // as_uid = 0 wenn hauptaccount oder keiner angehangen
	  $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = '".$uid."') OR (uid = '".$uid."') ORDER BY username");
	} else if ($as_uid != 0) {
	  //id des users holen wo alle an gehangen sind 
	  $get_all_users = $db->query("SELECT uid,username FROM " . TABLE_PREFIX . "users WHERE (as_uid = '".$as_uid."') OR (uid = '".$uid."') OR (uid = '".$as_uid."') ORDER BY username");
	}
	while ($users = $db->fetch_array($get_all_users)) {
	  $uid = $users['uid'];
	  $charas[$uid] = $users['username'];
	}
	return $charas;  
}
