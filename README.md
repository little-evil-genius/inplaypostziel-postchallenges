# Inplaypostziel & Post-Challenges
Mit diesem Plugin kann für einen beliebigen Zeitraum ein Inplaypost-Ziel vorgegeben werden für das gesamte Forum. Zusätzlich haben User die Möglichkeit persönliche Post-Challenges zu starten.<br>
Zum bietet es die Möglichkeit von einem sogenannten Inplaymarathon, bei dem das Team einen bestimmten Zeitraum festlegen kann. Innerhalb dieses Zeitraums können Ziele für die Anzahl von geschriebenen Posts, Wörtern und Zeichen vom Team gesetzt werden, die für das ganze Forum gälten. Die Zählung der einzelnen Posts erfolgt automatisch, ohne dass sich die User vorher anmelden müssen. Alle Beiträge aus den gewünschten Bereichen des Forums werden berücksichtigt. Eine Anzeige auf dem Index informiert die User über den laufenden Marathon. Die Darstellung der einzelnen Ziele kann individuell angepasst werden. Zudem können Toplisten aktiviert werden, die die Charaktere und Spieler nach ihrer Postzahl auflisten. Zusätzlich werden auch die Anzahl der geschriebenen Zeichen und Wörter aufgelistet.<br>
<br>
Zum anderen bietet dieses Plugin den User die Möglichkeit, eigene und persönliche Postziele, sogenannte Post-Challenges, im Usercp festzulegen. Hierbei können sie sich ein Postziel in einer festgelegten Anzahl von Tagen setzen und optional auch ein Wort- und Zeichenziel definieren. Die Fortschritte können im Usercp verfolgt werden. Wenn das Team ein entsprechendes Thema eingerichtet hat, können die User mit einem Klick melden, wenn sie eine Post-Challenge erfolgreich abgeschlossen haben. Eine automatische Nachricht mit allen wichtigen Informationen wird dann in diesem Thema gepostet. Zusätzlich gibt es Übersichtsseiten für aktive und vergangene Post-Challenges vom gesammten Forum.

# Vorrausetzung
- Der <a href="https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/enhanced-account-switcher/" target="_blank">Accountswitcher</a> von doylecc <b>muss</b> installiert sein.
  
# Datenbank-Änderungen
hinzugefügte Tabelle:
- PRÄFIX_user_postchallenges

# Einstellungen
- Inplayarea
- ausgeschlossene Foren	
- persönlichen Challenges aktiv	
- Übersicht aller persönlichen Post-Challenges	
- Übersicht alle persönlichen Post-Challenges - Berechtigungen	
- Meldethema für die abgeschlossenen persönlichen Post-Challenges	
- Postmaraton aktiv	
- Startdatum	
- Enddatum	
- ausgeschlossene Accounts	
- Post-Ziel	
- Wörter-Ziel	
- Zeichen-Ziel	
- Fortschrittanzeige	
- Rangliste aktivieren	
- Limit der Rangliste	
- Spielername<br>
<br>
<b>HINWEIS:</b><br>
Das Plugin ist kompatibel mit den klassischen Profilfeldern von MyBB und dem <a href="https://github.com/katjalennartz/application_ucp">Steckbrief-Plugin</a> von <a href="https://github.com/katjalennartz">risuena</a>. Das Plugin ist unabhängig von einem Inplaytrackersystem.

# Neues Templates (nicht global!)
- postinggoal_challenges_overview_active
- postinggoal_challenges_overview_bit
- postinggoal_challenges_overview_finished
- postinggoal_challenges_overview_navigation
- postinggoal_challenges_usercp	
- postinggoal_challenges_usercp_active	
- postinggoal_challenges_usercp_add	
- postinggoal_challenges_usercp_characters	
- postinggoal_challenges_usercp_finished	
- postinggoal_challenges_usercp_nav	
- postinggoal_challenges_usercp_report	
- postinggoal_marathon_index	
- postinggoal_marathon_index_bit_count	
- postinggoal_marathon_index_bit_goal	
- postinggoal_marathon_index_bit_progressbar	
- postinggoal_marathon_index_toplist	
- postinggoal_marathon_toplist_bit	
- postinggoal_marathon_toplist_character	
- postinggoal_marathon_toplist_navigation	
- postinggoal_marathon_toplist_player

# Neue Variable
- index: {$postinggoal_marathon}

# Neues CSS - postinggoal.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern - auch im Default. Sonst kann es passieren, dass es bei einem Update von MyBB entfernt wird.
<blockquote>
.postinggoal_index {
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
    content: '';
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
}
</blockquote>

# Links
- Topliste nach Charakteren: LINK/misc.php?action=postgoals_character 
- Topliste nach Spieler*innen: LINK/misc.php?action=postgoals_player
- Übersicht aller aktiven Post-Challenges: LINK/misc.php?action=postchallenges_overview_active
- Übersicht aller vergangenen Post-Challenges: LINK/misc.php?action=postchallenges_overview_finished
- persönliche Challenges im UserCP: LINK/usercp.php?action=postchallenges

# Demo
