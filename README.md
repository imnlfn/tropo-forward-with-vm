# tropo-forward-with-vm
PHP application to implement call forwarding, with voicemail backup, using 
Tropo.

It depends on KLogger, from https://github.com/katzgrau/KLogger.

There is not yet any forwarding functionality included. All it does so far is 
process user input (four DTMF digits) during the voice prompt or record a
voicemail if the user enters nothing.

TODO:
1. Admin menu
2. Call forwarding (requires DB)
3. Handle incorrect PIN
4. Voicemail functions, such as listen to message, delete, etc. (requires DB) 