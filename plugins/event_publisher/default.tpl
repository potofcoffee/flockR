
Sehr geehrte Damen und Herren,

über eine Veröffentlichung der folgenden Veranstaltungsdaten in den 
Kirchlichen Nachrichten würden wir uns sehr freuen:


Veranstaltungen der Volksmission von {$range.start|date_format:"%e.%m.%Y"} bis {$range.end|date_format:"%e.%m.%Y"}

{foreach from=$events item=dayevents key=day}
- {$day|date_format:"%A, %e.%m."}
{foreach from=$dayevents item=event}{$event.start|date_format:"%H:%M"} Uhr: {$event.group.name}: {$event.kommentar|trim}{if $event.rota.Predigt.text} ({$event.rota.Predigt.list.0.nachname}){/if}

{/foreach}{/foreach} 