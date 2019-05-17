# twilio-click-to-call

A click-to-call WordPress plugin which allows your visitors to recieve a call from you. It uses [Twilio](https://www.twilio.com/) to make calls and send status messages. It also has a call log page to manage call logs and callers.

## How it works

You will need a twilio account, SID, Auth Key and a Twilio Phone Number.

It will be used to make the calls. It has a WordPress widget, visitors can input their phone number and click a button to get a call from you.
Behind the scenes, the twilio number is used to make a call to the number you registered in options and forwards the call to the number given by the visitor.