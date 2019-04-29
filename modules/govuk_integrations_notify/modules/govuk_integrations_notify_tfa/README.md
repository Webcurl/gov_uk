This module enables Two Factor Authentication (TFA) via the GOV.UK Notify SMS Gateway, setup in the parent module. The SMS Gateway needs to be set up according to the parent module instructions, along with the recommended Mobile Number field on Users for this module to be effective.

Steps to enable TFA with GOV.UK Notify:
- All previous steps as defined in the parent GOV.UK Notify. These steps must be actioned before the below configuration is setup, as there will be missing fields, API keys and other configuration otherwise.
- TFA   
    - Key
        - Generate a valid key for TFA. See the [Key](https://www.drupal.org/project/key) module for more information.
    - Encryption Profile
        - Create an Encyption profile, as defined in the [Encrypt](https://www.drupal.org/project/encrypt) module.
    - Two-factor Authentication
        - Required fields. These are the fields and their configuration required to utilise GOV.UK Notify as a TFA method.
            - Enable TFA
            - Define the Roles required to use TFA
            - Allowed Validation plugins -> "GOV.UK Notify Notify - Mobile Number"
            - Default validation plugin -> "GOV.UK Notify Notify - Mobile Number"
            - Extra Settings -> GOV.UK Notify - Mobile Number
                - Verification field - Defines the User Mobile Number field to use for TFA. Select the field created during the Mobile Number configuration steps.
            - Encryption Profile - The Encryption Profile to use for TFA. Select the Encryption Profile created during the earlier setup of TFA.
        - Other fields can be filled as you please. They are preferences for how TFA will function once enabled.

Once the above steps have been actioned, you should be able to enable 2 factor authentication on users as defined in TFA -> Roles required to set up TFA.