# XML Enterprise Directory Creator for Yealink & Netsapiens
This simple script was made to automatically generate XML directories for Yealink phones that are connected to the Netsapiens platform. It will automatically create a directory of shared company contacts (aka enterprise contacts) so that it can be deployed to all phones in a company. It will output a different directory for each domain that has a shared contact list. See **Installation and Usage** for details on how to create the shared contacts.

This script is nearly identical to my [XML Directory Creator](https://github.com/nesb0t/yealink-directory-creator) which generates directories for all internal users. The difference is this one generates directories for all external contacts (aka company contacts or enterprise contacts) that a company wishes to share on all phones. 

# Motivation
Users on our previous phone system were used to having a shared directory on their phone that listed all of the company's external contacts, and we wanted to provide that same functionality on Netsapiens.

This type of feature was posted as a feature request on the Netsapiens forum ([#545](https://forum.netsapiens.com/t/link-contacts-in-web-portal-to-show-on-yealink-directory/545/)) so I decided to release this for all to use. **I have added a TON of comments to the code, and broken it up in to several sections to make it easy to read**. Even those with very little PHP experience should be able to modify this to work for you.

# Important Note Before Using
This script searches for a specific user (which you define) on each domain that will house the shared contacts. Ideally you will create the exact same user for each domain that wants a shared contact list. If you don't then you'll have to run multiple copies of the script, or modify it to search for more than one account. **Installation and Usage** provides all the details on how to set it up.

# Installation and Usage
1. Determine the details you will use for the user that will be used as the source for the shared contacts. The script uses the following options by default: 
- First Name: Z Enterprise
- Last Name: Contacts
- Extension: 9805

  Without additional modifications to the script, or without running multiple copies of it, this needs to be same on every domain. You should then provide the client with the login details for this account so that they can add their contacts to it. 

2. Open yealink-enterprise-directory.php in your favorite text editor (don't use Notepad) and make the necessary changes to the items found in the header. The actual file contains comments next to each item to help you out.
```php
define("SERVER", "nms.example.com");
define("SUPERUSER", "directorycreator@example.com");
define("PASSWORD", "Strong-Password-Here");
define("CLIENTID", "Example_API_User");
define("CLIENTSECRET", "ExampleKey123");
define("DIRECTORYLOCATION", "/var/www/html/example/");

define("DIRECTORYFIRSTNAME", "Z Enterprise");
define("DIRECTORYLASTNAME", "Contacts");
define("DIRECTORYEXTENSION", "9805");
```
3. Place yealink-enterprise-directory.php in a non-web-accessible folder on any server that has PHP available.
4. Configure a cron job (Linux) or Scheduled Task (Windows) to run yealink-enterprise-directory.php automatically every hour (or however often you want).
```sh
0 * * * * php -q -f /home/example/protected/directory-creator/yealink-enterprise-directory.php > /dev/null 2>&1
```
- The directories will be output to the folder specified in DIRECTORYLOCATION. You will want this to be web accessible. Ensure the user running the cron job has permissions to write to that folder. 
- See the **Security** and **Troubleshooting** sections below for more tips and help.

# Deploying to Yealink Phones
Once the directories are created, you must deploy them to the phones using the remote directory feature. We do it in the NDP via domain overrides. If Netsapiens accepts feature request [#425](https://forum.netsapiens.com/t/ndp-adding-tokens-variables-to-overrides/425) then this will be even easier. If you want to thank me for this script, please go vote for it. :)

```php
remote_phonebook.data.1.url="http://example.com/directory/Example.com-contacts.xml"
remote_phonebook.data.1.name="Example"
features.remote_phonebook.enable="1"
features.remote_phonebook.flash_time="3600"
directory_setting.url="http://example.com/directory/favorite_setting.xml"
```

**Note:** If you are also using my [XML Directory Creator](https://github.com/nesb0t/yealink-directory-creator) for internal company contacts then you'll want to set this as a secondary phonebook by using data.2.url and data.2.name. 

Review Yealink documentation if you have any questions about this, and feel free to contact me if you're still stuck. With additional overrides you configure it so that it goes directly to this phonebook and disable the local phonebook entirely, as well as a few more things.

# Directory Examples
XML Output:
```xml
<?xml version="1.0" encoding="ISO-8859-1"?>
<YealinkIPPhoneDirectory>
<DirectoryEntry>
<Name>Company Contact 1</Name> 
<Telephone>9195551212</Telephone> 
</DirectoryEntry>
<DirectoryEntry>
<Name>Company Contact 2</Name> 
<Telephone>9198675309</Telephone> 
</DirectoryEntry>
</YealinkIPPhoneDirectory>
```

Screenshot on Yealink:

![Example Remote Directory](https://raw.githubusercontent.com/nesb0t/yealink-enterprise-directory-creator/master/github-example.png)

# Troubleshooting
- There are a number of items that are commented out by the pound sign (#) rather than my usual //. These items will also have a trailing comment that reads "*// Uncomment for basic debugging*." If you are having trouble getting this to work, you can uncomment those lines as necessary to figure out where it may be failing. Just run the file from the command line or make it web-accessible in your local environment and access it from your web browser.
- You may contact me if you have any additional questions.

# Tests
- PHP: Tested on PHP version 5.6.24. I have not tested on v7, but it should be fine.
- OS: Windows and Linux (Debian and Ubuntu).
- Phones: Tested on Yealink T23G, T41P, T42G, T46G, and T48G. Any model that supports their XML directories should be fine.

# Security
- Do not store the PHP script itself in a web-accessible folder. That is a huge risk to your API keys and passwords.
- Setup htaccess on the folder that the directories are written to (DIRECTORYLOCATION) and limit access to them. Some (non-foolproof) suggestions are to lock it down to certain IP addresses and/or to Yealink User-Agents. You can also obfuscate the file/folder names if you're concerned about someone scanning for them.
- The account that is used to access the API should be a Read Only Super User rather than just Super User.

# Contributors and Thanks
- I used Chris Aaker's doCurl function from [here](https://github.com/aaker/domain-selfsignup). Thanks, Chris!

# Disclaimer
I am not a developer by any means. I barely knew PHP when I started this. I can't promise that this won't cause any problems for you, up to and including your web server catching on fire. Use it at your own risk. You should test it on a sandbox before you use it on your production servers.

With that said, we have been using it for over a year without any issues. The API calls are all read-only, and you should be using a read-only account anyways, so it should be fine.

# License

I am releasing it under the MIT license which means you are welcome to use it for any and all purposes as long as you do not hold me liable. License details are below. You may contact me if you need it released under a different license.

MIT License

Copyright (c) 2016 Brent Nesbit

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.