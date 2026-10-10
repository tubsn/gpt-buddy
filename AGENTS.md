# Application Development with the Flundr MVC Framework

This file describes general development practices for projects built with the Flundr framework. It is intended to help both people and AI build small, readable MVC applications. The [Flundr Bootstrap repository](https://github.com/tubsn/flundr) shows the basic project structure. For deeper details, consult the flundr framework code in the [Flundr repository](https://github.com/tubsn/flundrCore) or in `vendor/flundr/core`. For common tasks, the guidance in this file should be sufficient without having to inspect the framework repository.

> **Guiding principle:** Human readability takes priority over token savings and performance optimizations. Write simple, well-structured code. Keep controllers small and move application logic and recurring operations into appropriate models. Check existing controllers, models, layouts, and CSS/JS files before creating new ones. Make the code easy to understand. The goal is to build small, maintainable applications with as little unnecessary complexity as possible. Security checks must remain in place.

## Contents

1. [Core Concept](#core-concept)
2. [Directory Structure](#directory-structure)
3. [PHP Style](#php-style)
4. [Routing and Controllers](#routing-and-controllers)
5. [Models and Database](#models-and-database)
6. [Views and Templates](#views-and-templates)
7. [CSS and JavaScript](#css-and-javascript)
8. [Authentication and Security](#authentication-and-security)
9. [Caching](#caching)
10. [Helper Functions and Core Tools](#helper-functions-and-core-tools)
11. [Configuration and Bootstrap](#configuration-and-bootstrap)
12. [Development Workflow and Maintenance](#development-workflow-and-maintenance)
13. [Operations and Deployment When Needed](#operations-and-deployment-when-needed)

## Core Concept

Flundr follows the Model–View–Controller (MVC) pattern: routes select a controller action; the controller coordinates the request; models handle data access and application logic; and views define layouts, can provide shared request preparation or access checks, and render the output using templates.

## Directory Structure

- `app/` contains application code: controllers, models, views, templates, configuration, routes, and bootstrap code.
- `logs/` contains application logs; `cache/` contains the file-based cache. Both directories must be writable and located outside the webroot.
- `public/` is the webroot and contains the entry point, `index.php`. It may also contain `robots.txt` and, for Apache, a `.htaccess` file.
- `public/styles/` contains CSS and JavaScript files served directly, usually under `css/` and `js/`. `public/styles/flundr/` provides, among other things, base and reset styles in `css/defaults.css` and reusable components.
- `vendor/` contains Composer dependencies and the autoloader. Manage packages through Composer and `composer.json`; do not edit code in `vendor/`.
- `.gitignore` specifies which local and generated files Git excludes. Credentials must not be committed to the repository, even if the file containing them is not currently tracked.

## PHP Style

- Use tabs for indentation.
- Put opening braces for classes and methods on the same line.
- Simplify nested `if/else` blocks with early returns where possible.
- Avoid ternary expressions or use them sparingly; a regular `if` statement is often easier to read.
- Use type declarations selectively when they provide a concrete benefit. Readability takes priority.
- Variables in `camelCase`. For abbreviations such as ID or URL, names like `$articleID` and `$articleURL` are fine.
- Use underscores in function and method names, such as `get_article()` or `refresh_cache()`.

```php
class Articles extends Model {

	public function find_published($articleID) {
		$article = $this->get($articleID);
		if (!$article) {return null;}
		if (!$article['published']) {return null;}

		return $article;
	}
}
```

Catch errors only where you can respond meaningfully. Not every method needs a `try/catch` for every possible exception. Security checks and necessary error handling must still be implemented.

## Routing and Controllers

In `app/config/routes.php`, `get()` and `post()` map URLs to actions in the `Controller@method` format. Flundr uses [nikic/fast-route](https://github.com/nikic/FastRoute) as its router. Regex constraints are supported, and route placeholders are passed to the action as arguments:

```php
$routes->get('/articles/{articleID:\d+}', 'Content@show_article');
```

Controller names should describe a functional area or task, such as `Reports`, `Authentication`, `Usermanagement`, `Settings`, or `API`. A controller reads the request, checks access where necessary, calls models, and selects an HTML response, JSON response, or redirect.

```php
class Content extends Controller {

	public function __construct() {
		$this->models('Articles,Events');
		$this->view('DefaultLayout');
	}

	public function show_article($articleID) {
		$article = $this->Articles->find_published($articleID);
		if (!$article) {throw new \Exception('Article not found', 404);}
		$this->view->article = $article;

		$this->view->events = $this->Events->list(10);

		$this->view->title = 'Anfrage nicht möglich';
		$this->view->render('content/article');
	}
}
```

Flundr passes exceptions to its error handler. If an `Error` controller exists, it receives the exception and can set the HTTP status from its code and render a custom error page. Without an `Error` controller, Flundr displays its own error page; in the example above, the exception code also serves as the HTTP status `404`.

`$this->models('Articles,Users')` registers models. When `$this->Articles->find_published($articleID)` is accessed for the first time, Flundr initializes the registered model. This is a convenience, not a requirement: `new Articles()` is also possible, especially when the constructor needs arguments. But using the $this->Model->method() Syntax is generally prefered. Controllers should not contain SQL queries or extensive application logic.

## Models and Database

Model names start with a capital letter. Where possible, they should correspond to the database table name and are usually plural, such as `Articles`, `Users`, or `Prompts`. A model can extend `flundr\mvc\Model` to use Flundr's database methods. Models for external services or calculations do not need a database and do not have to extend the base class.

```php
class Articles extends Model {

	public function __construct() {
		$this->db = new SQLdb(DB_SETTINGS);
		$this->db->table = 'articles';
	}
}
```

The base model provides methods including `get()`, `all()`, `search()`, `exact_search()`, `create()`, `update()`, and `delete()`. Domain-specific methods should group validation, queries, and write operations. For custom SQL, bind values using prepared statements; `Model::query()` executes a SQL string directly through the `SQLdb` class. Never use table or column names from requests without validating them.

There is currently no fixed migration workflow for schema changes. Tables are often maintained with phpMyAdmin and transferred through import/export. Document schema changes clearly within each project.

## Views and Templates

An HTML view also acts as a page layout. It can define CSS, JavaScript, page titles, template blocks, default variables, and commonly used helper functions. The view constructor can also contain shared middleware-like logic, such as preparation or access checks.

Applications can use and adapt the existing `DefaultLayout`. A separate layout makes sense when pages genuinely need a different shared structure, resources, or preparation.

`$this->view('DefaultLayout')` selects the view class. Prefer setting template variables individually as view properties. This makes it clear in the controller which data the page receives. Passing a data array as the second argument to `render()` is also possible, but is not the preferred style:

```php
$this->view->category = $categoryData;
$this->view->article = $this->Articles->list(3);

$this->view->title = 'Articles';
$this->view->render('content/article');
```

`render('content/article')` loads the main template at `app/templates/content/article.tpl`. Layout defaults from `templateVars` are also available; render data passed to `render()` can override them. `$page` contains metadata such as `$page['title']` and is reserved as a variable name. Include partial templates with `include tpl('content/navigation')`.

**Template style:** Templates are normally formatted HTML/PHP source code and should not be condensed. Please try to format the template files so that the are comfortably human readable. 

```php
<main class="main-content">
	<section class="article-list">
		<header class="section-heading">
			<span class="section-kicker">Knowledge</span>
			<h2>Articles</h2>
			<p><?=$articleCount?> articles</p>
		</header>

		<div class="article-grid">
			<?php foreach ($articles as $article): ?>
				<article class="article-card">
					<h3><?=$article['title']?></h3>
					<p><?=$article['summary']?></p>
				</article>
			<?php endforeach ?>
		</div>
	</section>
</main>
```

Write PHP short echo tags without extra spaces: `<?=$variable?>`. Do not define a local `$escape` closure at the beginning of every template or add escaping calls indiscriminately to every output. Models and controllers validate data according to application requirements; controllers or views can prepare values for a particular output. `gnum()` can format numbers using German notation. Regular HTML views also provide `$this->view->json($data)`, which sets the JSON header and encodes the data. `$this->view->referer($url)` stores a return URL; `$this->view->referer()` retrieves it. `$this->view->back($fallback)` redirects to that URL or to the fallback. Use `$this->view->redirect($url)` for a direct destination.

## CSS and JavaScript

CSS and JavaScript files can be included directly without a build step. This keeps them easy to read, edit, and work with using AI tools. No bundler is prescribed, but one can be added when needed. Check existing assets before creating new files: `main.css` and `main.js` are sufficient for simple applications. More complex applications may benefit from separate CSS files for individual views or layouts.
**defaults.css:** Important: The Defaultview is loading a defaults.css file. If you don´t need this you should disable it in the view instead of overwriting styles. Be aware the the defaults.css file can mess up your styles if you don´t consider it!

Organize stylesheets by page area and component. Fonts can be configured through `$fonts` in a view or layout. For a font used throughout a layout, this is usually clearer than an `@import` in the CSS file.

**JavaScript structure:** For interactive interfaces with their own state and multiple actions, prefer a class or create a vue app. This keeps state, DOM access, events, and rendering methods together in a clear unit. A small script can remain simple; avoid long IIFEs with many mutable variables and functions. Vue is a good option for a frontend framework: it can be included without a bundler and integrated as a Vue app in a template.

Additional JavaScript or Vue components can be imported as ES modules when needed, for example: `import Dropdown from './components/dropdown-menu.js';`.

A view can centrally define files for a layout through `$css`, `$js`, `$framework`, `$fonts`, and `$modules`. This avoids repeating includes in individual templates. Flundr makes these values available as `$page['css']`, `$page['js']`, `$page['framework']`, `$page['fonts']`, and `$page['modules']`; the relevant header template must output them.

For ES modules, a view can set `public $modules = ['/styles/js/main.js'];`, for example. The header template, such as `app/templates/layout/html-doc-header.tpl`, then renders `<script type="module" src="/styles/js/main.js"></script>`. Frequently used dependencies can optionally be preloaded there with `<link rel="modulepreload" href="/styles/js/components/dropdown-menu.js">`. Check how assets are included in the specific project.

## Authentication and Security

For session-based logins, Flundr provides methods including `Auth::logged_in()`, `Auth::has_right()`, and `Auth::has_group()`. For API endpoints, `JWTAuth` can create and verify tokens. `Auth::valid_ip()` provides an additional IP check. The appropriate combination depends on the action. Templates can also use helpers such as `logged_in()`, `auth_rights()`, and `auth_groups()` to check login status, rights, and groups.

The Flundr Bootstrap project includes login and user-management examples through the `Authentication` and `Usermanagement` controllers.

- Expose only `public/` as the webroot. `.env`, `app/`, `logs/`, and `cache/` must not be directly accessible over HTTP. Do not put credentials or tokens in Git, logs, or public files.
- Protect state-changing forms and requests against CSRF; the HTML view provides `$CSRFToken` in templates.
- Validate input at the appropriate boundary. Parameterize custom SQL queries. Handle untrusted content according to its HTML, attribute, URL, or JavaScript output context before rendering it dynamically.
- Validate redirect destinations derived from request data, including when using `referer()` and `back()`. Allow external destinations only deliberately.
- For uploads, check file type and size, choose safe filenames, and decide where files are stored and whether they are served publicly.
- In production, use HTTPS and appropriate cookie settings. Keep internal details out of exception messages shown publicly; a custom `Error` controller can control user-facing messages. Do not expose stack traces or secrets to users.

Further information about the auth process can be looked up in the auth classes in `/flundr/core/auth`.

## Caching

`Cache::get($key)` reads an entry, while `Cache::set($key, $value, $ttl)` stores it with a lifetime in seconds. A cache miss returns `null`. A cache key can be a string such as `'articles-overview'` or an array of parameters such as `['articles', $category]`. It must include every factor that changes the result:

```php
$cacheKey = ['articles', 'overview'];
$articles = Cache::get($cacheKey);

if ($articles === null) {
	$articles = $this->load_overview();
	Cache::set($cacheKey, $articles, 3600);
}
```

To refresh data, write a new value using the same key. The `RequestCache` class also provides `delete()` and `flush()`. Keep cache access close to the model that supplies the data, and cache sensitive data only after making a deliberate decision to do so.

## Helper Functions and flundr Tools

The flundr file `utility/HelperFunctions.php` provides functions including `tpl()`, `session()`, `auth()`, `dump()`, and `dd()`. `dump($data)` outputs data and continues execution; `dd($data)` outputs data and ends the request. These are development tools and do not belong in production responses.

For session data, use `Session::get()`, `Session::set()`, and `Session::unset()`. The `flundr\utility\Log` class provides `write()` and `error()`; after a `use` statement, these can be called as `Log::write()` and `Log::error()`. Other core components cover email (`message/Email.php`), file storage, and image processing (`file/Storage.php`, `file/Thumbnail.php`). Check their exact APIs in the flundr vendor folder when needed.

## Configuration and Bootstrap

Credentials and other secrets belong in the untracked `.env` file; `example.env` can show the required settings without real credentials. General application configuration lives in `app/config/config.php`, often as constants. The startup process can be customized through `bootstrap.php`, for example by loading configuration and additional initialization code. Check the exact loading sequence in the project at hand.

For a new Flundr installation:

1. Install Composer dependencies and copy or rename `example.env` to `.env`; enter the credentials there.
2. Review the existing example controllers, models, and templates. Remove examples you do not need.
3. Check the existing `DefaultLayout`, login, and user management before creating alternatives.

## Development Workflow and Maintenance

When making a change, first trace the route, controller, models, view, and template involved. Then update the logic where it belongs and test the affected request. Automated tests are not currently a standard part of Flundr projects; add an appropriate test workflow when needed.

Put recurring services in an existing model when they fit its responsibility, or in a separate model when they do not. New abstractions should provide a concrete simplification.

## Operations and Deployment When Needed

Before publishing, briefly check that the webroot points to `public/`, `.env` contains the correct credentials, Composer dependencies are installed, `logs/` and `cache/` are writable, and production configuration is active. Check HTTPS, session cookies, and error output; back up the database before schema changes and visit the most important routes after deployment. This section concerns operations and is not an extra step for every routine code change.