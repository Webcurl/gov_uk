Steps to set up:
- GOV.UK Notify
    - API Key. Set up the API Key(s) to utilise on GOV.UK Notify for the integration.
        - Login to GOV.UK Notify
        - Go to "API integration"
        - Go to "API keys"
        - "Create an API key"
        - Provide a valid name for the API key.
        - Choose the correct type of API key
            - Live -> Live integration with GOV.UK Notify
            - Team and whitelist -> Test integration. Actually sends text messages. Only sends to members of GOV.UK Notify service and specific whitelisted numbers.
            - Test -> Test integration. Doesn't send messages, but does log them within GOV.UK Notify.
        - COPY THE KEY. This key will not be shown again, so save it somewhere.
    - Templates
        - Login to GOV.UK Notify
        - Go to "Templates"
        - Create or edit a template.
        - Within the template, you MUST use the following tokens/placeholders:
            - ((website))
            - ((code))
        - Save your template
        - Copy the Template ID. It will always show within "Templates", so you do not need to save this anywhere.


- SMS Framework
    -  Gateways
        - Create a new SMS Gateway
            - Select the "GOV.UK Notify" Gateway.
            - Under "GOV.UK NOTIFY"
                - Provide your GOV.UK Notify API Key.
                - Provide your GOV.UK Notify Template ID.
            - Save.
    
    
- Mobile Number
    - Field
        - Create a new Mobile Number field on Users.
        - In the Field setup (Edit), Ensure that the Verification Message contains "!code". This is the Verification code which gets sent through to GOV.UK Notify. Without this token working, GOV.UK Notify will not send out a valid verification code.

This will setup a Mobile Number field on Users which, when you try to verify the mobile number, will send a text via GOV.UK Notify.