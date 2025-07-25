<main>

<style>
	p+h3 {margin-top: 2em;}
</style>

<div class="box" style="max-width:1200px; margin:0 auto; margin-top:2em">
<b>Ai "Humor" Ecke:</b> <?=$funfact?>
</div>


<section class="box" style="max-width:1200px; margin:0 auto; margin-top:2em">

<h1><?=APP_NAME?> Changelog</h1>
<hr>

<h3>20. Juli 2025 - Uploadfunktion für Bildgenerator</h3>
<p>Es ist jetzt möglich eigene Bilder für die Bildverbesserung hochzuladen.</p>


<h3>05. Juni 2025 - Andocken des neuen Bildgenerators</h3>
<p>Im Bildgenerator steht jetzt das "gpt-image-1"-Modell zur Verfügung. Es verbessert die Qualität der erstellten Bilder erheblich und kann bereits bestehende Bilder weiterbearbeiten. Achtung: Zur Nutzung dieses Modells ist es notwendig, sein Unternehmen bei OpenAI zu authentifizieren (https://platform.openai.com/settings/organization/general).</p>


<h3>21. April 2025 - Zufallsgenerator und Datum in Prompts und Eingaben</h3>
<p>Im Prompting steht jetzt eine einfache Templatesprache mit 3fach Mustache-Logik {{{ TOKEN }}} zur Verfügung.
Daruch lassen sich z.B. über {{{ rot | grün | blau }}} Per Zufall entweder rot, grün oder blau ausgeben.
Desweiteren Lässt sich mit {{{ now }}} der aktuelle Zeitpunkt als Y-m-d H:i einfügen oder mit {{{ date bzw. time }}} nur Datum oder Uhrzeit anzeigen. Falls weitere Tokens für euch nützlich sind, sagt bescheid.</p>


<h3>19. April 2025 - Aufräumarbeiten im Edit Prompt UI</h3>
<p>Für Nachbearbeitungsprompts steht jetzt mehr Platz zur Verfügung.</p>

<h3>16. April 2025 - Integration neuer Reasoning und Audio Modelle sowie GPT-4.1</h3>
<p>Für die Integration der o3 und o4 Reasoning Modelle mussten anpassungen an Streaming, Reasoning und Temperature Einstellungen vorgenommen werden. Achtung: Das o3 Streaming funktioniert nur, nach vorheriger Freischaltung als Organisation auf der OpenAI Seite.</p>

<h3>7. April 2025 - Integration von Search Model</h3>
<p>Mit dem Search Model gpt-4o-search ist es jetzt möglich direkt Livedaten aus dem Internet zu beziehen und die Ki auch zu aktuellen Themengebieten zu nutzen. Das Search Model greift dazu nach Eingabe des Nutzers automatisch auf eine interne OpenAI Suchmaschine zu und sammelt Informationen zum gestellten Thema.</p>

<h3>12. März 2025 - Kontrollprompt</h3>
<p>Es gibt jetzt eine zusätzliche Möglichkeit nach der Usereingabe einen Kurzen Kontrollprompt als Systemprompt zu aktivieren. Dazu in den Prompteinstellungen ein sogenannter Kontrollprompt aktiviert werden. Anders als beim Nachbearbeitungsprompt wird kein zweiter Aufruf an die KI gesendet, sondern das Gespräch durch einen "verstärkten" Zusatz erweitert.</p>

<h3>01. März 2025 - Reasoning Modelle mit Streaming</h3>
<p>Die Streaming Funktion bei Reasoning Modellen o3-Mini, o1 usw. funktioniert jetzt. Ebenso steht das Model 4.5 zur Auswahl.</p>

<h3>10. Februar 2025 - Nachbearbeitungs-Prompts - Chain of Thought</h3>
<p>Mit Hilfe eines Nachbearbeitungs-Prompts können zwei Promptaktionen in Reihe geschaltet werden. Es wird zunächst der eigentliche Hauptprompt auf die Usereingabe angewandt. Das daraus resultierende Ergebnis wird anschließend durch den zweiten (Nachbearbeitungs-)Prompt verfeinert. Der User erhält als Ausgabe nur das verfeinerte Ergebnis. Dadurch können "Chain of Thought"-Ketten aufgebaut werden.</p>

<h3>6. Februar 2025 - Modell-Auswahlbox</h3>
<p>Die GPT4-Aktivieren-Funktion wurde durch eine Modell-Auswahl-Box ersetzt. Es ist somit möglich, zwischen mehreren Modellen hin und her zu wechseln. Die verfügbaren Modelle können über die config.php hinterlegt werden. Ein Beispiel dazu findet sich ab sofort in der example.config.php.</p>

<h3>2. Februar 2025 - o3-Modell-Vorbereitungen</h3>
<p>Das Reasoning-Modell o3 ist jetzt in den Varianten o3 und o3-mini verfügbar und kann als Modell eingesetzt werden. Achtung: Die neuen Modelle unterstützen keine Chat-Streaming-Funktion.</p>

<h3>29. Januar 2025 - Multiple Knowledgebases</h3>
<p>Es ist jetzt möglich, in einem Prompt gleich mehrere Knowledgebases zu verknüpfen. Diese werden einfach kommagetrennt aufgelistet, z.B. "Userneedinfos, Polizeimeldungen".</p>
<p>Dadurch ist es möglich, mehrere URL-Quellen gleichzeitig anzuzapfen (Achtung: hier kann es schnell etwas länger dauern, da die Webseiten zur Laufzeit gecrawlt werden müssen).</p>

<h3>9. Januar 2025 - URL-Import für Knowledgebases</h3>
<p>Für die Knowledgebases steht jetzt ein generischer URL-Import zur Verfügung, welcher über den Webserver URLs einlesen kann und dessen Inhalte nach selbst definierten CSS-Selektoren filtern kann. Dadurch sind z.B. Anwendungszwecke wie automatisches Einlesen von aktuellen Pressemeldungen möglich.</p>
<p>Der Nutzer kann dann per Prompt Fragen zu diesen Pressemeldungen stellen, Zusammenfassungen für Newsletter erstellen, kritische Nachrichten filtern usw.</p>

<h3>8. Januar 2025 - Text-to-Speech</h3>
<p>Es gibt jetzt die Möglichkeit, den Ein- oder Ausgabetext als gesprochenes Audio wiederzugeben (TTS). Dazu steht aktuell das Open AI Whisper Model mit verschiedenen Sprechern zur Verfügung.</p>

<h3>25. September 2024 - Bilder-löschen-Option</h3>
<p>Im Bildgenerator ist es jetzt möglich, Bilder wieder zu entfernen. Dazu muss der Nutzer die Berechtigungsstufe "deleteimage" erhalten.</p>

<h3>13. September 2024 - Multi-Option-Importer</h3>
<p>Die Multi-Import-Funktion ermöglicht es, ungeordnete Datensammlungen mit Hilfe eines Prompts zu sortieren und in eine Datenbank zur späteren Weiterverwendung zu importieren. Dabei können Texte, Bilder oder Screenshots als Datengrundlage importiert werden.</p>
<p>Es handelt sich hier um ein sehr spezifisches Feature, welches nicht out of the Box funktioniert. Bei Interesse gern Rückfragen.</p>

<h3>18. Mai 2024 - GPT-4o Model</h3>
<p>Für alle Anfragen im GPT-4-Modus wird jetzt standardmäßig das GPT-4o (Omni) Modell verwendet. Es ist günstiger und schneller als die bisherigen GPT-4-Modelle. Aktueller Wissensstand des Modells ist Oktober 2023.</p>
<p>Außerdem wurde die maximale Token-Beschränkung für die Ausgabe deaktiviert. Sie liegt jetzt immer bei 4096 Tokens, dem aktuellen Standard für alle neueren Modelle sowie GPT-3.5.</p>

<h3>02. Mai 2024 - Bildupload und GPT-Vision Model</h3>
<p>Über den Dateiupload ist es jetzt möglich, JPG, PNG und WEBP-Dateien hochzuladen und diese dann über GPT-Vision erkennen zu lassen. Damit ist es möglich, Probleme mit Skizzen zu beschreiben, z.B. erstelle mir eine Tabelle in HTML mit folgenden Vorgaben... Hochgeladene Bilddaten lassen sich durch Klick auf das Thumbnail bzw. "x" wieder entfernen.</p>
<p>Damit die Bilderkennung funktioniert, muss zwingend GPT4 aktiviert sein. (wird beim Upload automatisch angeschaltet)</p>

<h3>27. Januar 2024 - Duplizieren von Prompts</h3>
<p>Prompts lassen sich jetzt im Konfigurationsmenü duplizieren.</p>

<h3>23. Januar 2024 - Userinterface-Verbesserungen</h3>
<p>Für mehr Kontrolle bei der Textgenerierung gibt es einen neuen Button, mit dem die Textgenerierung jederzeit beendet werden kann. Außerdem wurden die Buttons zum Löschen des Verlaufs verschoben, um mehr Klarheit zu schaffen.</p>
<p>Prompt-Editierungs-UI optisch verbessert.</p>
<p>Einträge im Verlauf können jetzt angeklickt werden, dadurch wird der Verlauf in das aktuelle Eingabefeld kopiert.</p>

<h3>17. Dezember 2023 - Umbau der Conversation-Speicher-Logik</h3>
<p>Für jede Conversation werden jetzt neben dem eigentlichen Dialog auch Metadaten wie der verwendete Prompt, die Uhrzeit und die Prompttemperatur mitgespeichert. Dadurch ist es z.B. möglich, die Temperatur des Modells je nach Prompt einzustellen. In den Prompt-Einstellungen finden sich die entsprechenden Optionen dafür.</p>
<p>Achtung: In der MySQL-Datenbank muss dafür ein entsprechendes Feld (temperature) angelegt werden.</p>
<p>Zusätzlich wurde die Darstellung von Fehlern im Chatstream verbessert.</p>

<h3>14. Dezember 2023 - Neues Onboarding-Setup</h3>
<p>Die Git-Struktur wurde für Neuinstallationen optimiert. .env, config.php, custom.css und die Hauptnavigation enthalten jetzt Beispieldateien, die (für Neuinstallationen) vom automatischen Updateprozess ausgeschlossen sind.</p>

<h3>12. Dezember 2023 - API für Prompt-Export eingeführt</h3>
<p>Mittels Token-Authentifizierung lassen sich jetzt Prompts unter /prompts auslesen, z.B. um sie in einem externen CMS nutzbar zu machen.</p>

<h3>17. November 2023 - Bildergalerien im Bildgenerator</h3>
<p>Generierte Bilder lassen sich jetzt in einer Galerieansicht öffnen. Außerdem wurde ein Pager eingeführt, damit nicht immer alle Fotos geladen werden.</p>

<h3>07. November 2023 - Überarbeitung der Dall-E-Schnittstelle</h3>
<p>Der Bildgenerator wurde überarbeitet. Als Modell läuft künftig Dall-E3, was höhere Auflösung, Hoch- und Querformate und weitaus bessere Ergebnisse liefert.</p>

<h3>06. November 2023 - GPT-4-Turbo verfügbar</h3>
<p>Das generative Modell GPT-4-Turbo ermöglicht schnellere Antwortzeiten bei gleichzeitig mehr Verarbeitungspower für eingegebene Texte. Das neue Modell kann eingangsseitig bis zu 128.000 Tokens verarbeiten. Außerdem wurde der Trainingsdatensatz bis zu einem Wissensstand im April 2023 erweitert.</p>

<h3>27. Oktober 2023 - Eingabetext beibehalten und löschen</h3>
<p>Der Eingabetext wird jetzt beim Wechsel in einen neuen Menüpunkt beibehalten. Zusätzlich gibt es einen neuen Button, um Eingabetext gezielt löschen zu können.</p>

<h3>19. Oktober 2023 - Prompt-Historie</h3>
<p>Beim Bearbeiten eines Prompts werden ab jetzt immer die letzten 10 Versionen zwischengespeichert, so kann ein Prompt bei Bedarf wiederhergestellt werden.</p>

<h3>18. Oktober 2023 - Audio-Transkribierung</h3>
<p>Über den Datei-Upload ist es jetzt möglich, Audiodateien, z.B. im MP3-Format, hochzuladen. Die Daten werden über die OpenAI Whisper-Schnittstelle ausgelesen, in lesbaren Text umgewandelt und in das Eingabefeld geschrieben. Die Texte können somit sofort durch weitere Prompts bearbeitet werden.</p>

<h3>14. September 2023 - Datei-Upload</h3>
<p>Über den Button "Datei hinzufügen" lassen sich jetzt Dateien importieren, um beispielsweise PDF-Pressemitteilungen schneller einfügen zu können. Aktuell werden PDF-, HTML- und Textdateien erkannt.</p>

<h3>06. September 2023 - Direktlink für Prompts</h3>
<p>Es lassen sich jetzt gezielt Prompts über einen Direktlink "anspringen", sodass man Kollegen per Link dorthin verweisen kann. Dazu aktualisiert sich der Link in der Browseradresszeile automatisch, wenn man einen Prompt auswählt.</p>

<h3>26. Juli 2023 - GPT-4 verfügbar</h3>
<p>Seit heute steht für unseren OpenAI-Account die Nutzung von GPT-4 zur Verfügung. Das Modell bietet verbesserte Verarbeitungskapazitäten und generell besseres Verständnis für komplexere Aufgaben.</p>
<p>Das GPT-4-Modell lässt sich individuell per Checkbox an- und abschalten. Als Standard ist weiterhin GPT-3.5-Turbo hinterlegt.</p>

<h3>23. Juni 2023 - Code-Highlighting</h3>
<p>Fragen zur Programmierung werden jetzt über <a href="https://highlightjs.org/">HighlightJS</a> mit entsprechendem Syntax-Highlighting markiert.</p>

<h3>21. Juni 2023 - Nutzungsstatistiken</h3>
<p>Gespräche werden jetzt durch ChatGPT in einer Kategorie zusammengefasst. Diese Daten sowie eine tägliche Nutzungsauswertung stehen jetzt auf der <a href="/stats">Statistikseite</a> zur Verfügung.</p>

<h3>19. Juni 2023 - Erläuterungen und Hilfstexte</h3>
<p>Hilfestellung zum Erstellen von Prompts und Hinweise zu Kategorien eingefügt.<br>Außerdem gibt es jetzt eine "Just Chat"-Funktion.</p>

<h3>18. Juni 2023 - Direkt-Prompts und Callback-Funktionen</h3>
<p>Es ist jetzt möglich, einen Prompt auf Knopfdruck auszuführen, ohne dass der User noch zusätzliche Angaben machen muss, z.B. um ein Horoskop zu generieren.</p>
<b>Prompts mit Callbacks</b><br>
<p>Für einen Prompt kann eine Funktion hinterlegt werden, z.B. um Daten von einer externen Webseite zu beziehen. Dies eröffnet viele Möglichkeiten, z.B. um ein aktuelles Datum in der Usereingabe zu ergänzen oder vor der Abfrage eine interne Datenbank zu durchsuchen und das Ergebnis in den Prompt mit aufzunehmen, z.B. für Nachfragen über einen Termin in einem Marketingplan (Wyld-MMS).</p>

<h3>17. Juni 2023 - Prompt-Verwaltungs-Update</h3>
<p>Die Promptverwaltung wurde komplett überarbeitet. Es ist jetzt möglich, Prompts nach verschiedenen Rubriken zu sortieren und diese dann entsprechend auf Unterseiten oder für verschiedene Nutzergruppen gefiltert anzuzeigen.</p>
<p>Die Prompts werden dabei nicht mehr als Datei gespeichert, sondern liegen in einer Datenbank, was eine weitere Skalierung ermöglicht.</p>

<ul>
    <li>Zusammenfassung von spannenden Artikeln auf der Startseite cachen und darstellen.</li>
    <li>Prompts können mit Berechtigungsstufen an- und abgestellt werden.</li>
    <li>Promptverwaltung-Layout überarbeitet.</li>
</ul>

<h3>16. Juni 2023 - Darkmode</h3>
<p>Es steht ab sofort ein Darkmode für den Buddy zur Verfügung. Yey!</p>

<h3>3. Juni 2023 - Antworten-Streams</h3>
<p>Antworten werden jetzt gestreamt, d.h. der Nutzer kann während der Generierung die Antwort bereits anlesen, was zu mehr Produktivität führt.</p>

<h3>27. Mai 2023 - Interne API für Mehrfachabfragen</h3>
<p>Die Programmbasis wurde aufgesplittet, sodass Userinterface (Prompteingabe) und Abfragetunnel (Anbindung an ChatGPT-API) in separaten Bereichen funktionieren.
Dadurch ist sichergestellt, dass parallel mehrere Nutzeranfragen gleichzeitig verarbeitet werden können. Das System ist dadurch flexibel aufrüstbar, solange bis wir an die ChatGPT-API-Vorgaben (zur Zeit 3,5 Abfragen pro Minute) stoßen. Aber auch das ließe sich über mehrere API-Konten weiter skalieren.</p>

<h3>12. Mai 2023 - Github Open Source</h3>
<p>Der Buddy-Code kann für Studienzwecke bei Github eingesehen werden.</p>

<h3>xx. April 2023 - Start</h3>
<p>Projekt ins Leben gerufen.</p>

</section>

</main>
