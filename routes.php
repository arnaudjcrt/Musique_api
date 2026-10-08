<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Middleware\JwtHelper;
use App\Middleware\JwtMiddleware;
use App\Repository\ArtistRepository;
use App\Repository\AlbumRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {

    $app->options('/{routes:.*}', function (
        Request $request,
        Response $response
    ) {
        return $response;
    });

    $app->get('/', function (
        Request $request,
        Response $response
    ) {
        $response->getBody()->write('Welcome SNIR!');
        return $response;
    });


    // =========================================
    // LOGIN JWT
    // =========================================

    $app->post('/login', function (
        Request $request,
        Response $response
    ) {

        $data = (array) $request->getParsedBody();

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if ($username !== 'arnaud' || $password !== '1234') {

            $response->getBody()->write(
                json_encode([
                    'error' => 'Invalid credentials'
                ], JSON_THROW_ON_ERROR)
            );

            return $response
                ->withHeader(
                    'Content-Type',
                    'application/json'
                )
                ->withStatus(401);
        }

        $token = JwtHelper::generateToken([
            'username' => $username
        ]);

        $response->getBody()->write(
            json_encode([
                'token' => $token
            ], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader(
                'Content-Type',
                'application/json'
            )
            ->withStatus(200);
    });


    // =========================================
    // USERS DU SQUELETTE SLIM
    // =========================================

    $app->group('/users', function (Group $group) {

        $group->get(
            '',
            ListUsersAction::class
        );

        $group->get(
            '/{id}',
            ViewUserAction::class
        );
    });


    // =========================================
    // TOUS LES ARTISTES
    // =========================================

    $app->get('/GetAllArtist', function (
        Request $request,
        Response $response
    ) {

        $repo = $this->get(
            ArtistRepository::class
        );

        $artists = $repo->findAll();

        $response->getBody()->write(
            json_encode(
                $artists,
                JSON_THROW_ON_ERROR
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );

    })->add(new JwtMiddleware());


    // =========================================
    // ARTISTE PAR ID
    // =========================================

    $app->get('/GetArtist/{id}', function (
        Request $request,
        Response $response,
        array $args
    ) {

        $repo = $this->get(
            ArtistRepository::class
        );

        $artist = $repo->findById(
            (int) $args['id']
        );

        $response->getBody()->write(
            json_encode(
                $artist ?? [
                'error' => 'Artiste introuvable'
            ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response
            ->withHeader(
                'Content-Type',
                'application/json'
            )
            ->withStatus(
                $artist ? 200 : 404
            );

    })->add(new JwtMiddleware());


    // =========================================
    // ARTISTES PAR ANNEE
    // =========================================

    $app->get('/GetArtistByYear/{annee}', function (
        Request $request,
        Response $response,
        array $args
    ) {

        $repo = $this->get(
            ArtistRepository::class
        );

        $artists = $repo->findByYear(
            (int) $args['annee']
        );

        $response->getBody()->write(
            json_encode(
                $artists,
                JSON_THROW_ON_ERROR
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );

    })->add(new JwtMiddleware());


    // =========================================
    // AJOUTER UN ARTISTE
    // =========================================

    $app->post('/AddArtist', function (
        Request $request,
        Response $response
    ) {

        $repo = $this->get(
            ArtistRepository::class
        );

        $data = (array) $request->getParsedBody();

        $id = $repo->insert($data);

        $response->getBody()->write(
            json_encode([
                'idArtist' => $id
            ], JSON_THROW_ON_ERROR)
        );

        return $response
            ->withHeader(
                'Content-Type',
                'application/json'
            )
            ->withStatus(201);

    })->add(new JwtMiddleware());


    // =========================================
    // MODIFIER UN ARTISTE
    // =========================================

    $app->put('/UpdateArtist/{id}', function (
        Request $request,
        Response $response,
        array $args
    ) {

        $repo = $this->get(
            ArtistRepository::class
        );

        $data = (array) $request->getParsedBody();

        $success = $repo->update(
            (int) $args['id'],
            $data
        );

        $response->getBody()->write(
            json_encode([
                'success' => $success
            ], JSON_THROW_ON_ERROR)
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );

    })->add(new JwtMiddleware());


    // =========================================
    // SUPPRIMER UN ARTISTE
    // =========================================

    $app->delete('/DeleteArtist/{id}', function (
        Request $request,
        Response $response,
        array $args
    ) {

        $repo = $this->get(
            ArtistRepository::class
        );

        $success = $repo->delete(
            (int) $args['id']
        );

        $response->getBody()->write(
            json_encode([
                'success' => $success
            ], JSON_THROW_ON_ERROR)
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );

    })->add(new JwtMiddleware());


    // =========================================
    // TOUS LES ALBUMS
    // =========================================

    $app->get('/GetAllAlbums', function (
        Request $request,
        Response $response
    ) {

        $repo = $this->get(
            AlbumRepository::class
        );

        $albums = $repo->findAll();

        $response->getBody()->write(
            json_encode(
                $albums,
                JSON_THROW_ON_ERROR
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );

    })->add(new JwtMiddleware());


    // =========================================
    // ALBUMS D'UN ARTISTE
    // =========================================

    $app->get('/GetAlbumsByArtist/{id}', function (
        Request $request,
        Response $response,
        array $args
    ) {

        $repo = $this->get(
            AlbumRepository::class
        );

        $albums = $repo->findByArtist(
            (int) $args['id']
        );

        $response->getBody()->write(
            json_encode(
                $albums,
                JSON_THROW_ON_ERROR
            )
        );

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );

    })->add(new JwtMiddleware());

};