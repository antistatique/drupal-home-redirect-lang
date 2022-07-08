# Homepage Redirect Language

Redirect visitors landing on the Homepage to their preferred language, based on previous browsing session.
Offering their native content as default.

This system uses Cookies (`home_redirect_lang_preferred_langcode`) to redirect visitors to the best matching Drupal
defined language (if the cookie exists).

This module will perform the language redirect only on the Homepage and only once the user preferred language has been
detected (aka when the visitor has chosen to change the language of any page).
Once the person has chosen to browse the website in any language, the language preference will be stored in a cookie
(via JavaScript).
This cookie will then be used to trigger redirection when visiting the homepage on another language than the preferred
stored language.

By using this behaviour (JavaScript storage and Cookie preferred language), we still allow visitors to change the
homepage language and visit the website in any language.

_Note there is no redirection trigger on any other page than the homepage._

As you may notice, the first visit can’t be handled - by design - with a non-existing cookie. In order to redirect a
client upon its first visit, you can enable the module configuration “Fallback redirection using visitor browser
preferred language.” This configuration will be used only when the Cookies (`home_redirect_lang_preferred_langcode`) does not exist.

## Supporting custom Language Switcher

By default, this module will attach a custom JavaScript library to the Drupal core Language Switcher in order to
create the `home_redirect_lang_preferred_langcode` cookie.

Still, you can create your own JavaScript and use the `home_redirect_lang/common` library to create the cookie.

1. Add the `home_redirect_lang/common` on your own theme

```yaml
libraries:
  - home_redirect_lang/common
```

2. Update your own JavaScript to use the cookie creation from the common library

```javascript
Drupal.homeRedirectLang.setPreferredLanguage('fr');
```

```javascript
let links = document.querySelectorAll('.language-link');
links.forEach(box => {
  box.addEventListener('click', function (event) {
    var hreflang = event.target.getAttribute('hreflang');
    Drupal.homeRedirectLang.setPreferredLanguage(hreflang);
  });
});
```

## Supporting organizations

This project is sponsored by [Antistatique](https://www.antistatique.net), a Swiss Web Agency.
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).

## Getting Started

We highly recommend you to install the module using `composer`.

```bash
$ composer require drupal/home_redirect_language
```

## Warning

In order for this module to works properly for anonymous user, you must disable the `pache_cache` module.

> the Internal Page Cache assumes that all pages served to anonymous users will
> be identical, regardless of the implementation of cache contexts.
See https://www.drupal.org/docs/drupal-apis/cache-api/cache-contexts#internal
