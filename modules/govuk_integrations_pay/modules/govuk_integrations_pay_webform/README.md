This module provides a Webform Element for use within Webforms.

Steps to setup:
- Go to the relevant Webform that needs to be GOV.UK Pay enabled.
- Add element -> Advanced Elements -> GOV.UK Pay
- Fields:
    - Amount Provider 
        - Defines how an amount is provided to GOV.UK Pay
        - Webform element 
            - Retrieves the value from another element. Restricted to:
                - Hidden element
                - Number element
                - Radios element
                - Radios Other element
                - Select element
                - Select Other element
                - Value element
                - Computed Token element
                - Computed Twig element
        - Static amount 
            - Provide a static amount to send to GOV.UK Pay.
    - Use default content? 
        - If ticked, utilise a set of default content to display in place of the element on the form to inform users about the redirection to GOV.UK Pay.
        - If unticked, it is expected that you will provide your own markup to explain this process.
    - GOV.UK Pay Summary
        - Text that displays on the GOV.UK Pay page once the user is sent across to GOV.UK Pay.
        - This text is restricted to 255 characters, as per GOV.UK Pay restrictions.
        - This text is also not allowed any type of special character.
    - Confirmation message
        - Markup to display to the user once they are redirected frmo GOV.UK Pay back to your site once payment has been sent.
          