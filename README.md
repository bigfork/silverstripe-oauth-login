# SilverStripe OAuth Login

SilverStripe OAuth2-based login functionality, based on the PHP League's [OAuth2 client](http://oauth2-client.thephpleague.com/) and the [SilverStripe OAuth module](https://github.com/bigfork/silverstripe-oauth).

## \*\* IMPORTANT \*\*

Please note that this module is still in early development and should **not** be used in a production environment. It has not been fully tested, and may undergo significant changes before a stable release.

### What this module does
This module adds “Log in with &lt;provider&gt;” buttons to SilverStripe’s default login form, which will authenticate a user with the chosen provider and store an OAuth access token. It also provides configurable access token scopes (or permission levels) and field mapping for storing user data on registration.

## Installation

This module must be installed with composer. Run `composer require bigfork/silverstripe-oauth-login:*` from the command line, and then run a `dev/build`.

## Configuration

**NOTE:** You must first configure your OAuth providers using the configuration options detailed in the [SilverStripe OAuth2 module documentation](https://github.com/bigfork/silverstripe-oauth#configuration).

To show a login button for a configured provider, you must add them to the new `Authenticator` class’ YAML configuration. The configuration has two options avaiable: `name` (shown on the “Login as X” button, how this is configured may change in future releases) and `scopes` (the desired scopes/permission levels for the access token).

Following on from the Facebook example in the [SilverStripe OAuth2 module documentation](https://github.com/bigfork/silverstripe-oauth#configuration):

```yml
Bigfork\SilverStripeOAuth\Client\Factory\ProviderFactory:
  providers:
    'Facebook':
      class: 'League\OAuth2\Client\Provider\Facebook'
      constructor_options:
        clientId: '12345678987654321'
        clientSecret: 'geisjgoesingoi3h1521onnro12rin'
        graphApiVersion: 'v2.6'
Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator:
  providers:
    'Facebook': # Matches the internal name configured above
      name: 'The Facebooks'
      scopes: ['email', 'public_profile']
```

---

## Concepts

### Minimum scope requirements

At the very minimum, the provider must return an email address used to associate a token with a user. Email-less authentication may be possible in future releases, though has not been investigated yet.

### Stale tokens

After successfully authenticating, this module will currently remove any old access tokens for the provider used for authentication as they are effectively stale. This behaviour may change in future releases.

### Mappers

When the user registers for the first time with a provider, they will not yet have an associated `Member` record in the SilverStripe database. To create that record, this module attempts to copy information from the resource owner returned by the provider.

The default behaviour is to attempt to copy email, first name and surname, though this behaviour can be altered in one of two ways:

#### Using `GenericMemberMapper`

The default mapper (`Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper`) will attempt to copy fields from a mapping array that can be configured in YAML, for example:

```yml
Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper:
  mapping:
    'Facebook':
      'Town' => 'Hometown' # Town is the SilverStripe db column, 'Hometown' is in the data returned by Facebook
      'Gender' => 'Gender'
```

#### Using a custom mapper

If more detailed or complex mapping is needed, you can create your own mapper class to handle it. Just implement  `Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface`, set up your mapping logic, and then register your new mapper in YAML:

```yml
Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory:
  mappers:
    'Facebook': 'Mysite\MyFacebookMapperClass'
```

---

## Todo

- Signing up via standard email + password, then attempting to log in using oauth with an account matching that email will currently fail. Probably needs to be handled better
- See if templates/extension points can be used to make the login buttons more easily customisable
- What should happen if I sign in with Facebook, then Google using the same email address? Should one profile's data overwrite the other? Priority based? Separate accounts?
