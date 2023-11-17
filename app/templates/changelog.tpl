<main>

<style>
	p+h3 {margin-top: 2em;}
</style>

<div class="box" style="max-width:1200px; margin:0 auto; margin-top:2em">
<b>Ai "Humor" Ecke:</b> <?=$funfact?>
</div>


<section class="box" style="max-width:1200px; margin:0 auto; margin-top:2em">

<h1>Ai-Buddy Changelog und Roadmap</h1>
<hr>

<h3>17. November 2023 - Bildergallerien im Bildgenerator</h3>
<p>Generierte Bilder lassen sich jetzt in einer Galerieansicht öffnen. Außerdem wurde ein Pager eingeführt damit nicht immer alle Fotos geladen werden.</p>

<h3>07. November 2023 - Überarbeitung der Dall-E Schnittstelle</h3>
<p>Der Bildgenerator wurde überarbeitet. Als Model läuft künftig Dall-E3 was höhere Auflösung, Hoch- und Querformate und weitaus bessere Ergebnisse liefert.</p>

<h3>06. November 2023 - GPT-4-Turbo Verfügbar</h3>
<p>Das generative Model GPT-4-Turbo ermöglicht schnellere Antwortzeiten bei gleichzeitig mehr Verarbeitungspower für eingegebene Texte. Das neue Model kann eingangsseitig bis zu 128.000 Tokens verarbeiten. Außerdem wurde der Trainingsdatensatz bis zu einem Wissenstand im April 2023 erweitert.</p>

<h3>27. Oktober 2023 - Eingabetext beibehalten und löschen</h3>
<p>Der Eingabetext wird jetzt beim Wechsel in einen neuen Menupunkt beibehalten. Zusätzlich gibt es einen neuen Button um Eingabetext gezielt löschen zu können.</p>

<h3>19. Oktober 2023 - Prompt Historie</h3>
<p>Beim Bearbeiten eines Prompts werden ab jetzt immer die letzten 10 Versionen zwischengespeichert, so kann ein Prompt bei Bedarf wiederhergestellt werden.</p>

<h3>18. Oktober 2023 - Audio Transkribierung</h3>
<p>Über den Datei Upload ist es jetzt möglich Audiodatein z.B. im Mp3 Format hochzuladen. Die Daten werden über die OpenAI Whisper Schnittstelle ausgelesen, in lesbaren Text umgewandelt und in das Eingabefeld geschrieben. Die Texte können somit sofort durch weiteren Prompts bearbeitet werden</p>

<h3>14. September 2023 - Datei - Upload</h3>
<p>Über den Button Datei hinzufügen lassen sich jetzt Dateien importieren um beispielsweise PDF-Pressemitteilungen schneller einfügen zu können. Aktuell werden PDF, HTML und Textdateien erkannt.</p>

<h3>06. September 2023 - Direktlink für Prompts</h3>
<p>Es lassen sich jetzt gezielt Prompts über einen Direktlink "anspringen", sodass man Kollegen per Link dort hin verweisen kann. Dazu aktualisiert sich der Link in der Browseradresszeile  automatisch wenn man einen Prompt auswählt.</p>

<h3>26. Juli 2023 - GPT-4 Verfügbar</h3>
<p>Seit heute steht für unseren OpenAI Account die Nutzung von GPT-4 zur Verfügung. Das Modell bietet verbesserte Verarbeitungskapazitäten und generell besseres Verständnis für komplexere Aufgaben.</p>
<p>Das GPT-4 Modell lässt sich individuell per Checkbox an- und abschalten. Als Standard ist weiterhin GPT-3.5-Turbo hinterlegt.</p>

<h3>23. Juni 2023 - Code Highlighting</h3>
<p>Fragen zu Programierung werden jetzt über <a href="https://highlightjs.org/">HighlightJS</a> mit entsprechendem Syntaxhighlighting markiert.</p>

<h3>21. Juni 2023 - Nutzungsstatistiken</h3>
<p>Gespräche werden jetzt durch ChatGPT in einer Kategorie zusammengefasst. Diese Daten sowie eine tägliche Nutzungsauswertung stehen jetzt auf der <a href="/stats">Statistikseite</a> zur Verfügung.</p>

<h3>19. Juni 2023 - Erläuterungen und Hilfstexte</h3>
<p>Hilfestellung zum erstellen vom Prompts und Hinweise zu Kategorien eingefügt.<br>Außerdem gibt es jetzt eine "Just Chat" funktion.</p>

<h3>18. Juni 2023 - Direkt Prompts und Callback Funktionen</h3>
<p>Es ist jetzt möglich einen Prompt auf Knopfdruck auszuführen, ohne das der User noch zusätzliche Angaben machen muss. z.B. um ein Horoskop zu generieren.</p>
<b>Prompts mit Callbacks</b><br>
<p>Für einen Prompt kann eine Funktion hinterlegt werden, z.B. um Daten von einer externen Webseite zu beziehen. Dies eröffnet viele Möglichkeiten z.B. um ein aktuelles Datum in der Usereingabe zu ergänzen. Oder vor der Abfrage eine Interne Datenbank zu durchsuchen und das Ergebnis in den Prompt mit aufzunehmen. Z.B. für Nachfragen über einen Termin in einem Marketingplan (Wyld-MMS).</p>

<h3>17. Juni 2023 - Prompt Verwaltungs Update</h3>
<p>Die Promptverwaltung wurde komplett überarbeitet. Es ist jetzt möglich Prompts nach verschiedenen Rubriken zu sortieren und diese dann Entsprechend auf Unterseiten oder für Verschiedene Nutzergruppen gefiltert anzuzeigen.</p>
<p>Die Prompts werden dabei nicht mehr als Datei gespeichert sondern liegen in einer Datenbank. Was eine weitere Skalierungen ermöglicht.</p>

<ul>
	<li>Zusammenfassung von Spannenden Artikeln auf der Startseite cachen und Darstellen.</li>
	<li>Prompts können mit Berechtigungsstufen an- und abgestellt werden.</li>
	<li>Promptverwaltung Layout überarbeitet</li>
</ul>

<h3>16. Juni 2023 - Darkmode</h3>
<p>Es steht ab sofort ein Darkmode für den Ai-Buddy zur verfügung. Yey!</p>

<h3>3. Juni 2023 - Antworten Streams</h3>
<p>Antworten werden jetzt gestreamt d.h. der Nutzer kann während der generierung die Antwort bereits anlesen. Was zu mehr produktivität führt.</p>

<h3>27. Mai 2023 - Interne Api für Mehrfachabfragen</h3>
<p>Die Programmbasis wurde aufgesplittet, so dass Userinterface (Prompteingabe) und Abfragetunnel (Anbindung an ChatGPT Api) in separaten Bereichen funktionieren.
Dadurch ist sichergestellt, dass parallel mehrere Nutzeranfragen gleichzeitig verarbeitet werden können. Das System ist dadruch flexibel aufrüstbar, solange bis wir an die ChatGPT Api vorgaben (zur Zeit 3,5 Abfragen pro Minute) stoßen. Aber auch das ließe sich über mehrere API Konten weiter skalieren.</p>

<h3>12. Mai 2023 - Github Open Source</h3>
<p>Der Ai-Buddy Code kann für Studienzwecke bei Github eingesehen werden.</p>

<h3>xx. April 2023 - Start</h3>
<p>Projekt ins Leben gerufen</p>

</section>

</main>
