# tropo-forward-with-vm
PHP application to implement call forwarding, with voicemail backup, using 
Tropo.

It depends on KLogger, from https://github.com/katzgrau/KLogger.

There is not yet any forwarding functionality included. All it does so far is 
process user input (four DTMF digits) during the voice prompt or record a
voicemail if the user enters nothing. There are now the beginnings of some 
menus, but only the options that go to another menu work at present.

I've created a MySQL database to support the application and will include a
schema when the design is more mature. The login information is in the file
CONFIG.PHP, which I have not included.

I am currently having issues getting session information from Tropo, so that 
code is commented out and I have set the variable $called in CONFIG.PHP as 
well. 

TODO:
1. Call forwarding
2. Limit attempts for incorrect PIN
3. Voicemail functions, such as listen to message, delete, etc.
4. Figure out how to get session information, so code can not only be generic,
   but the same page could serve for many different numbers.