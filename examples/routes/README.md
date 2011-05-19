// Create new Route objects manually
Dispatcher::addRoutes(
	array(
		new Route(
			"/", 
			"App\Views\Base::index"
		),
		new Route(
			"/blog/", 
			"App\Views\Blog::index"
		),
		new Route(
			"/blog/(?P<year>\d{4})/(?P<month>\d{2})/(?P<day>\d{2})", 
			"App\Views\Blog::article"
		),
		new Route(
			"/hello",
			function(){ return _("Hello world!"); }
		),
		new Route(
			"/test.php",
			function(){ return _("Hello world!"); }
		)
	)
);

// Use array
$routes = array(
	"/" 
		=> "App\Views\Base::index",
	"/tools/"
		=> "@App\Views\Tools",
	"/blog/"
		=> "App\Views\Blog::index",
	"/blog/(?P<year>\d{4})/(?P<month>\d{2})/(?P<day>\d{2})"
		=> "App\Views\Blog::article",
	"/blog/@year"
		=> "App\Views\Blog::articlesByYear",
	"/blog/@year/@month"
		=> "App\Views\Blog::articlesByMonth",
	"/hello"
		=> function(){ return "Hello world!"; }
);
Dispatcher::addRoutes($routes);

// Add routes with JSON
$json = json_encode(array(
	new Route(
		"/", 
		"App\Views\Base::index"
	),
	new Route(
		"/blog/", 
		"App\Views\Blog::index"
	),
	new Route(
		"/blog/(\d+)/(\d+)/(\d+)", 
		"App\Views\Blog::article"
	),
	new Route(
		"/hello",
		'function(){ return _("Hello world!"); }'
	)
));
Dispatcher::addRoutes($json, Dispatcher::JSON);

// Add routes with YAML
$yaml = <<<'YAML'
routes:
- pattern: /
  target: App\Views\Base::index
- pattern: /blog/
  target: App\Views\Blog::index
- pattern: /blog/(\d+)/(\d+)/(\d+)
  target: App\Views\Blog::article
  ttl: 3600
- pattern: /hello
  target: function(){ return "Hello world!"; }
YAML;
Dispatcher::addRoutes($yaml, Dispatcher::YAML);

// Add routes with file/url
Dispatcher::addRoutes("urls.yaml", Dispatcher::FILE);