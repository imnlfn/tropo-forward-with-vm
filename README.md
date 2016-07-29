# tropo-forward-with-vm
## PHP application to implement call forwarding, with voice mail backup, using Tropo.

### Dependencies
* KLogger, from https://github.com/katzgrau/KLogger
* Tropo PHP library (included)

### Notes
In its current state, this application will allow a caller to leave a voice mail, but there is no way to check those once recorded. It will also process user input during the original prompt and play menu options for the correct PIN (four DTMF digits). The menu options that work are to set forwarding (but only to the calling number, from Caller ID) and to disable forwarding. If call forwarding is set and the caller enters nothing, the call will be forwarded instead of being sent to voice mail.

I have had a couple issues with the Tropo PHP library, but was able to come up with workarounds:

1. The Session object is broken completely, as implemented. I tried several bits of code that use the Session object from Tropo's main site and its GitHub sites and *none* of them has worked. I ended up directly accessing the session information that Tropo passes instead and that worked just fine, and the code is only slightly less clear.
2. The transfer method is slightly broken, in that it needs to have an array passed as its second parameter in order for it not to generate warning messages. In its most basic form, with the developer passing just a number to use for the transfer, the resulting warning messages will interfere with the JSON it returns to Tropo and cause the transfer to fail. One option is to disable warnings, I suppose, but passing an array -- even an empty one -- as a second parameter will make them go away.

I have created a MySQL database to support the application and will include a schema when the design is more mature. Limited information from each call session is stored in a table that other parts of the application may use. The database credentials are in the file CONFIG.PHP, which I have not included, for obvious reasons. I have also added code so that setting the variable $debug_on in CONFIG.PHP will use other variables defined there in places where otherwise the only way to get their values is accessing POST data. This will allow the developer to test functionality by calling up the PHP page directly.

Finally, I have added the file TEXT.PHP, as a start to handling texts to the same number. I have yet to have even its basic functions work, though.

### To Do
1. Limit attempts for incorrect PIN
2. Voicemail functions, such as listen to message, delete, etc.
3. Allow caller the choice between forwarding to the number from Caller ID or entering a number.
4. Add a function that will keep the function table from getting too large.
5. (Optional) Add the ability to handle texts to this number as well as calls.