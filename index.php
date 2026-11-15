<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\Admin\DashboardController as AdminDashboard;
use App\Controllers\Admin\ImportController;
use App\Controllers\Admin\DestinationController as AdminDestination;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\LogController;
use App\Controllers\Researcher\DashboardController as ResearcherDashboard;
use App\Controllers\Researcher\ExportController as ResearcherExport;
use App\Controllers\Tourist\DashboardController as TouristDashboard;
use App\Controllers\Tourist\PreferencesController;
use App\Controllers\Tourist\FavouriteController;
use App\Controllers\Tourist\DestinationController as TouristDestination;
use App\Controllers\Tourist\ReviewController;
use App\Controllers\Tourist\AjaxController;
use App\Core\Router;
use App\Core\View;

$router = new Router();

// Home
$router->get('/', function (): void {
    $title   = config('app.name');
    $content = View::render('home.index');
    require BASE_PATH . '/app/Views/layouts/main.php';
});

// Auth Routes
$auth = new AuthController();
$router->get('/login',     [$auth, 'showLogin']);
$router->post('/login',    [$auth, 'login']);
$router->get('/register',  [$auth, 'showRegister']);
$router->post('/register', [$auth, 'register']);
$router->post('/logout',   [$auth, 'logout']);

// Dashboard
$router->get('/dashboard', [new DashboardController(), 'index']);

// Admin Routes
$adminDash = new AdminDashboard();
$router->get('/admin/dashboard', [$adminDash, 'index']);

$import = new ImportController();
$router->get('/admin/import', [$import, 'index']);
$router->post('/admin/import/upload', [$import, 'upload']);

$adminDest = new AdminDestination();
$router->get('/admin/destinations', [$adminDest, 'index']);
$router->get('/admin/destinations/edit/{id}', [$adminDest, 'edit']);
$router->post('/admin/destinations/update/{id}', [$adminDest, 'update']);
$router->post('/admin/destinations/toggle/{id}', [$adminDest, 'toggleStatus']);

$user = new UserController();
$router->get('/admin/users', [$user, 'index']);
$router->post('/admin/users/store', [$user, 'store']);
$router->post('/admin/users/toggle/{id}', [$user, 'toggle']);

$log = new LogController();
$router->get('/admin/logs', [$log, 'index']);

// Researcher Routes
$researcherDash = new ResearcherDashboard();
$router->get('/researcher/dashboard', [$researcherDash, 'index']);

$researcherExport = new ResearcherExport();
$router->get('/researcher/export', [$researcherExport, 'exportCsv']);

// Tourist Routes
$touristDash = new TouristDashboard();
$router->get('/tourist/dashboard', [$touristDash, 'index']);

$prefs = new PreferencesController();
$router->post('/tourist/preferences', [$prefs, 'save']);

$fav = new FavouriteController();
$router->post('/tourist/favourites/toggle/{id}', [$fav, 'toggle']);
$router->get('/tourist/favourites', [$fav, 'index']);

$touristDest = new TouristDestination();
$router->get('/tourist/destination/{id}', [$touristDest, 'show']);

$review = new ReviewController();
$router->get('/tourist/reviews', [$review, 'index']);
$router->post('/tourist/reviews/store/{id}', [$review, 'store']);
$router->post('/tourist/reviews/delete/{id}', [$review, 'delete']);

$ajax = new AjaxController();
$router->get('/tourist/new-destinations', [$ajax, 'newDestinations']);

// Normalize path
$uri     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$script  = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(str_replace('\\', '/', dirname($script)), '/');
if ($baseDir !== '' && str_starts_with($uri, $baseDir)) {
    $uri = substr($uri, strlen($baseDir)) ?: '/';
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);