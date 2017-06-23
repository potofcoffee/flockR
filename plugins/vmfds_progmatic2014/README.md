vmfds_progmatic2014
===================

kOOL module to export room reservations to ProgMatic 2014 .dat files for heating

Our church (http://www.volksmission-freudenstadt.de) uses programmable thermostats from EuroTronic to regulate heating 
in different rooms throughout the week. EuroTronic has proprietary software and a special programming stick (ProgMatic 2014) 
to pre-program a week on the pc and then transfer it to the individual thermostat units. The EuroTronic software reads
and writes binary .dat files to save programming sets on the local harddrive.
This kOOL plugin uses our php_progmatic library to write such .dat files based on room reservations in kOOL.

Author: Christoph Fischer (christoph.fischer@volksmission.de) for Volksmission Freudenstadt (http://www.volksmission-freudenstadt.de)
