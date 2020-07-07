# SilverStripe OAuth Login

SilverStripe OAuth2-based login functionality, based on the PHP League's [OAuth2 client](http://oauth2-client.thephpleague.com/) and the [SilverStripe OAuth module](https://github.com/bigfork/silverstripe-oauth).

### What this module does
This module adds “Log in with &lt;provider&gt;” buttons to SilverStripe’s default login form, which will authenticate a user with the chosen provider. It also provides configurable access token scopes (or permission levels) and field mapping for storing user data on registration.

## Installation

This module must be installed with composer. Run `composer require bigfork/silverstripe-oauth-login:*` from the command line, and then run a `dev/build`.

## Configuration

**NOTE:** You must first configure your OAuth providers using the configuration options detailed in the [SilverStripe OAuth2 module documentation](https://github.com/bigfork/silverstripe-oauth#configuration).

To show a login button for a configured provider, you must add them to the new `Authenticator` class’ YAML configuration. The configuration has two options avaiable: `name` (shown on the “Login as X” button, how this is configured may change in future releases) and `scopes` (the desired scopes/permission levels for the access token).

Following on from the Facebook example in the [SilverStripe OAuth2 module documentation](https://github.com/bigfork/silverstripe-oauth#configuration):

```yml
SilverStripe\Core\Injector\Injector:
  Bigfork\SilverStripeOAuth\Client\Factory\ProviderFactory:
    properties:
      providers:
        'Facebook': '%$FacebookProvider'
  FacebookProvider:
    class: 'League\OAuth2\Client\Provider\Facebook'
    constructor:
      Options:
        clientId: '12345678987654321'
        clientSecret: 'geisjgoesingoi3h1521onnro12rin'
        graphApiVersion: 'v6.0'
Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator:
  providers:
    'Facebook': # Matches the key for '$%FacebookProvider' above
      name: 'The Facebooks'
      scopes: ['email', 'public_profile']
```

## Customisation

You can customise the look of the login actions for each provider by creating the relevant template, following the naming convention `FormAction_OAuth_<ProviderName>`. For example:

```html
<!-- themes/mysite/templates/FormAction_OAuth_Facebook.ss -->
<button type="submit" name="{$Name}" id="{$ID}" class="facebook-login">
    Connect with Facebook
</button>
```

The `Bigfork\SilverStripeOAuth\Client\Form\LoginForm` class also provides two extension points, `updateFields` and `updateActions` for further customisation.

## Error handling

When a provider returns successfully, but returns an error state (for example, when a user chooses to reject the permissions you’re asking for), this module will attempt to return the user to the login screen and display a human-readable error message. As each provider returns error messages in different formats, you may need to add your own error handler in the event that the default handler is unable to show a suitable message. For example:

```yml
Bigfork\SilverStripeOAuth\Client\Control\Controller:
  error_handlers:
    loginerrorhandler:
      priority: 10
      context: login
      class: 'MyLoginErrorHandler'
```

```php
use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;

class MyLoginErrorHandler implements ErrorHandler
{
    public function handleError(AbstractProvider $provider, HTTPRequest $request, Exception $exception)
    {
        $message = $request->getVar('some_error_message_get_var');
        if ($message) {
            return Security::permissionFailure(null, $message);
        }
    }
}
```

---

## Concepts

### Passports

Each member that authenticates via an OAuth provider is assigned a “Passport” - a record which is unique to each OAuth account owner. This allows one SilverStripe account to be linked to multiple OAuth providers, or even linked to multiple individual accounts on the same provider. While both of those are possible, neither is the default behaviour for this module: by default, each new OAuth account will create a new SilverStripe member record. See the [multiple providers/accounts](#multiple-providers-accounts) and [email collisions](#email-collisions) sections for more information.

### Mappers

When the user registers for the first time with a provider, they will not yet have an associated `Member` record in the SilverStripe database. To create that record, this module attempts to copy information from the resource owner returned by the provider.

The default behaviour is to attempt to copy email, first name and surname, though this behaviour can be altered in one of two ways:

#### Using `GenericMemberMapper`

The default mapper (`Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper`) will attempt to copy fields from a mapping array that can be configured in YAML, for example:

```yml
Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper:
  mapping:
    'Facebook':
      'Town': 'Hometown' # Town is the SilverStripe db column, 'Hometown' is in the data returned by Facebook
      'Gender': 'Gender'
```

#### Using a custom mapper

If more detailed or complex mapping is needed, you can create your own mapper class to handle it. Just implement  `Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface`, set up your mapping logic, and then register your new mapper in YAML:

```yml
Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory:
  mappers:
    'Facebook': 'Mysite\MyFacebookMapperClass'
```

### Multiple providers/accounts

The default behaviour for this module is to treat each OAuth account as a separate SilverStripe account. This is because every website will have bespoke requirements on how multiple accounts should be treated, for example:

- If I sign up with Facebook, and then want to also link my Twitter account - which account’s information (i.e. name, email address) should take priority?
- If I want to link my Twitter account to SilverStripe account A, but it already belongs to SilverStripe account B because I already signed up with it, what happens? Should this be disallowed, or should account B be deleted?
- What if I’m a very awkward person and have two Facebook accounts that I want linked to the same SilverStripe account?

It is up to you if, or how, to handle scenarios like this. The typical solution would be to add buttons for “Link X Account” that are shown to users in their account once they’ve authenticated initially.

### Email collisions

As it’s possible, and likely, for users to have accounts for multiple OAuth providers that each have the same email address, you may encounter an error similar to _“Can't overwrite existing member #123 with identical identifier (Email = foo@bar.com)”_. This is because the default behaviour for SilverStripe is to ensure that every member record has a unique email address. There are a few different ways to work around this:

- Change the `Member.unique_identifier_field` config setting to something other than `Email` (for example, `ID`)
- Update the config for [`GenericMemberMapper`](#using-genericmembermapper) for your providers, but omit the `Email` field
- Create a [custom mapper](#using-a-custom-mapper) that doesn’t import email addresses

### Replacing the default authenticator

If you’d like to replace the default authenticator, or change the internal name of the oauth authenticator, you will need to reset the list of authenticators first. You can achieve this with the following approach:

```yml
---
Name: app-auth-reset
After:
  - '#oauthauthenticator'
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Security\Security:
    properties:
      Authenticators: null
---
Name: app-auth
After:
  - '#app-auth-reset'
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Security\Security:
    properties:
      Authenticators:
        myoauthname: '%$Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator'
```
