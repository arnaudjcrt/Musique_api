<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Middleware\JwtMiddleware;
use App\Repository\AlbumRepository;
use App\Repository\ArtistRepository;
use App\Repository\RatingRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Middleware\JwtHelper;

return function (App $app): void {

    // Construction des réponses JSON.
    $json = static function (
        Response $response,
        mixed $data,
        int $status = 200
    ): Response {
        $response->getBody()->write(
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    };

    // OPTIONS
    $app->options('/{routes:.*}', function (
        Request $request,
        Response $response
    ): Response {
        return $response;
    });

    // Accueil
    $app->get('/', function (
        Request $request,
        Response $response
    ): Response {
        $response->getBody()->write('Hello world!');

        return $response;
    });

    // Utilisateurs
    $app->group('/users', function (Group $group): void {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    // =========================================================
    // ANCIENNES ROUTES ARTISTES
    // =========================================================

    $app->get('/GetAllArtist', function (
        Request $request,
        Response $response
    ) use ($json): Response {
        $db = $this->get(PDO::class);
        $statement = $db->query('SELECT * FROM `artists`');

        return $json(
            $response,
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    });

    $app->get('/getArtistById/{id}', function (
        Request $request,
        Response $response,
        array $args
    ) use ($json): Response {
        $db = $this->get(PDO::class);

        $statement = $db->prepare(
            'SELECT * FROM `artists` WHERE `idArtist` = :id'
        );

        $statement->execute(['id' => (int) $args['id']]);
        $artist = $statement->fetch(PDO::FETCH_ASSOC);

        return $json(
            $response,
            $artist ?: ['error' => 'Artiste introuvable'],
            $artist ? 200 : 404
        );
    });

    $app->post('/AddArtist', function (
        Request $request,
        Response $response
    ) use ($json): Response {
        $data = (array) $request->getParsedBody();

        if (empty($data['Name']) || empty($data['Annee'])) {
            return $json(
                $response,
                ['error' => 'Name et Annee sont obligatoires'],
                400
            );
        }

        $db = $this->get(PDO::class);

        $statement = $db->prepare(
            'INSERT INTO `artists` (`Name`, `Annee`, `Description`)
             VALUES (:name, :annee, :description)'
        );

        $statement->execute([
            'name' => $data['Name'],
            'annee' => $data['Annee'],
            'description' => $data['Description'] ?? null,
        ]);

        return $json(
            $response,
            [
                'message' => 'Artiste ajouté avec succès',
                'idArtist' => (int) $db->lastInsertId(),
            ],
            201
        );
    });

    // =========================================================
    // API PROTÉGÉE PAR JWT
    // =========================================================
    $app->post('/login', function (
        Request $request,
        Response $response
    ) use ($json): Response {
        $data = (array) $request->getParsedBody();

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        // Identifiants de ton ancien fichier.
        if ($username !== 'arnaud' || $password !== '1234') {
            return $json(
                $response,
                ['error' => 'Invalid credentials'],
                401
            );
        }

        $token = JwtHelper::generateToken([
            'username' => $username,
        ]);

        return $json($response, ['token' => $token]);
    });
    $app->group('/api', function (Group $group) use ($json): void {

        // =====================================================
        // ARTISTES
        // =====================================================

        // GET /api/artists/search/name?name=...
        $group->get('/artists/search/name', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $name = (string) ($request->getQueryParams()['name'] ?? '');

            $artists = $this->get(ArtistRepository::class)
                ->findByName($name);

            return $json($response, $artists);
        });

        // GET /api/artists/search?q=...
        $group->get('/artists/search', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $query = (string) ($request->getQueryParams()['q'] ?? '');

            $artists = $this->get(ArtistRepository::class)
                ->searchByName($query);

            return $json($response, $artists);
        });

        // GET /api/artists/year/2000
        $group->get('/artists/year/{year}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $artists = $this->get(ArtistRepository::class)
                ->findByYear((int) $args['year']);

            return $json($response, $artists);
        });

        // GET /api/artists/years/1990/2020
        $group->get('/artists/years/{from}/{to}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $artists = $this->get(ArtistRepository::class)
                ->findByYearRange(
                    (int) $args['from'],
                    (int) $args['to']
                );

            return $json($response, $artists);
        });

        // GET /api/artists
        $group->get('/artists', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $artists = $this->get(ArtistRepository::class)->findAll();

            return $json($response, $artists);
        });

        // GET /api/artists/1
        $group->get('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $artist = $this->get(ArtistRepository::class)
                ->findById((int) $args['id']);

            return $json(
                $response,
                $artist ?? ['error' => 'Artiste introuvable'],
                $artist !== null ? 200 : 404
            );
        });

        // POST /api/artists
        $group->post('/artists', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $data = (array) $request->getParsedBody();

            if (empty($data['Name']) || empty($data['Annee'])) {
                return $json(
                    $response,
                    ['error' => 'Name et Annee sont obligatoires'],
                    400
                );
            }

            $id = $this->get(ArtistRepository::class)->addArtist(
                (string) $data['Name'],
                (int) $data['Annee'],
                isset($data['Description'])
                    ? (string) $data['Description']
                    : null
            );

            return $json($response, ['idArtist' => $id], 201);
        });

        // PUT /api/artists/1
        $group->put('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $updated = $this->get(ArtistRepository::class)
                ->updateArtist(
                    (int) $args['id'],
                    (array) $request->getParsedBody()
                );

            return $json($response, ['updated' => $updated]);
        });

        // DELETE /api/artists/1
        $group->delete('/artists/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $deleted = $this->get(ArtistRepository::class)
                ->deleteArtist((int) $args['id']);

            return $json($response, ['deleted' => $deleted]);
        });

        // =====================================================
        // ALBUMS
        // =====================================================

        // GET /api/albums/search/title?titre=...
        $group->get('/albums/search/title', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $title = (string) ($request->getQueryParams()['titre'] ?? '');

            $albums = $this->get(AlbumRepository::class)
                ->findByTitle($title);

            return $json($response, $albums);
        });

        // GET /api/albums/search?q=...
        $group->get('/albums/search', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $query = (string) ($request->getQueryParams()['q'] ?? '');

            $albums = $this->get(AlbumRepository::class)
                ->searchByTitle($query);

            return $json($response, $albums);
        });

        // GET /api/albums/by-artist/1
        $group->get('/albums/by-artist/{artistId}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $albums = $this->get(AlbumRepository::class)
                ->findByArtistId((int) $args['artistId']);

            return $json($response, $albums);
        });

        // GET /api/albums
        $group->get('/albums', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $albums = $this->get(AlbumRepository::class)->findAll();

            return $json($response, $albums);
        });

        // GET /api/albums/1
        $group->get('/albums/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $album = $this->get(AlbumRepository::class)
                ->findById((int) $args['id']);

            return $json(
                $response,
                $album ?? ['error' => 'Album introuvable'],
                $album !== null ? 200 : 404
            );
        });

        // POST /api/albums
        $group->post('/albums', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $data = (array) $request->getParsedBody();

            if (empty($data['Titre']) || empty($data['Artist_idArtist'])) {
                return $json(
                    $response,
                    ['error' => 'Titre et Artist_idArtist sont obligatoires'],
                    400
                );
            }

            $id = $this->get(AlbumRepository::class)->addAlbum(
                (string) $data['Titre'],
                (int) $data['Artist_idArtist']
            );

            return $json($response, ['idAlbums' => $id], 201);
        });

        // PUT /api/albums/1
        $group->put('/albums/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $updated = $this->get(AlbumRepository::class)
                ->updateAlbum(
                    (int) $args['id'],
                    (array) $request->getParsedBody()
                );

            return $json($response, ['updated' => $updated]);
        });

        // DELETE /api/albums/1
        $group->delete('/albums/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $deleted = $this->get(AlbumRepository::class)
                ->deleteAlbum((int) $args['id']);

            return $json($response, ['deleted' => $deleted]);
        });

        // =====================================================
        // NOTES : RATINGS
        // =====================================================

        // GET /api/ratings
        $group->get('/ratings', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $ratings = $this->get(RatingRepository::class)->findAll();

            return $json($response, $ratings);
        });

        // GET /api/ratings/1
        $group->get('/ratings/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $rating = $this->get(RatingRepository::class)
                ->findById((int) $args['id']);

            return $json(
                $response,
                $rating ?? ['error' => 'Note introuvable'],
                $rating !== null ? 200 : 404
            );
        });

        // GET /api/albums/1/ratings
        $group->get('/albums/{albumId}/ratings', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $ratings = $this->get(RatingRepository::class)
                ->findByAlbumId((int) $args['albumId']);

            return $json($response, $ratings);
        });

        // POST /api/albums/1/ratings
        $group->post('/albums/{albumId}/ratings', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $data = (array) $request->getParsedBody();
            $grade = filter_var(
                $data['grade'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($grade === false || $grade < 1 || $grade > 5) {
                return $json(
                    $response,
                    ['error' => 'La note doit être un entier entre 1 et 5'],
                    400
                );
            }

            $id = $this->get(RatingRepository::class)->addRating(
                (int) $args['albumId'],
                $grade
            );

            return $json($response, ['idRatings' => $id], 201);
        });

        // PUT /api/ratings/1
        $group->put('/ratings/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $data = (array) $request->getParsedBody();
            $grade = filter_var(
                $data['grade'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($grade === false || $grade < 1 || $grade > 5) {
                return $json(
                    $response,
                    ['error' => 'La note doit être un entier entre 1 et 5'],
                    400
                );
            }

            $updated = $this->get(RatingRepository::class)
                ->updateRating((int) $args['id'], $grade);

            return $json($response, ['updated' => $updated]);
        });

        // DELETE /api/ratings/1
        $group->delete('/ratings/{id}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $deleted = $this->get(RatingRepository::class)
                ->deleteRating((int) $args['id']);

            return $json($response, ['deleted' => $deleted]);
        });

        // =====================================================
        // STATISTIQUES
        // =====================================================

        // GET /api/albums/1/rating-stats
        $group->get('/albums/{albumId}/rating-stats', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $stats = $this->get(RatingRepository::class)
                ->getAlbumStats((int) $args['albumId']);

            return $json(
                $response,
                $stats ?? ['error' => 'Album introuvable'],
                $stats ? 200 : 404
            );
        });

        // GET /api/albums/1/ratings/distribution
        $group->get('/albums/{albumId}/ratings/distribution', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $distribution = $this->get(RatingRepository::class)
                ->getAlbumRatingDistribution((int) $args['albumId']);

            return $json($response, $distribution);
        });

        // =====================================================
        // CLASSEMENTS : RANKINGS
        // =====================================================

        // GET /api/rankings/albums?limit=10
        $group->get('/rankings/albums', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);

            $ranking = $this->get(RatingRepository::class)
                ->getTopRatedAlbums($limit);

            return $json($response, $ranking);
        });

        // GET /api/rankings/albums/lowest-rated?limit=10
        $group->get('/rankings/albums/lowest-rated', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);

            $ranking = $this->get(RatingRepository::class)
                ->getLowestRatedAlbums($limit);

            return $json($response, $ranking);
        });

        // GET /api/rankings/albums/most-rated?limit=10
        $group->get('/rankings/albums/most-rated', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);

            $ranking = $this->get(RatingRepository::class)
                ->getMostRatedAlbums($limit);

            return $json($response, $ranking);
        });

        // GET /api/rankings/artists?limit=10
        $group->get('/rankings/artists', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 10);

            $ranking = $this->get(RatingRepository::class)
                ->getTopRatedArtists($limit);

            return $json($response, $ranking);
        });
        // Duel entre deux albums.
        $group->get('/duel/albums/{firstId}/{secondId}', function (
            Request $request,
            Response $response,
            array $args
        ) use ($json): Response {
            $firstId = filter_var($args['firstId'], FILTER_VALIDATE_INT);
            $secondId = filter_var($args['secondId'], FILTER_VALIDATE_INT);

            if (
                $firstId === false || $secondId === false ||
                $firstId < 1 || $secondId < 1 ||
                $firstId === $secondId
            ) {
                return $json(
                    $response,
                    ['error' => 'Indique deux identifiants positifs et différents.'],
                    400
                );
            }

            $result = $this->get(RatingRepository::class)
                ->compareAlbums($firstId, $secondId);

            if ($result === null) {
                return $json(
                    $response,
                    ['error' => 'Un des albums est introuvable.'],
                    404
                );
            }

            return $json($response, $result);
        });

// Découvrir des albums bien notés avec peu de votes.
        $group->get('/discover/hidden-gems', function (
            Request $request,
            Response $response
        ) use ($json): Response {
            $albums = $this->get(RatingRepository::class)
                ->findHiddenGems();

            return $json($response, [
                'criteres' => [
                    'moyenne_minimum' => 4,
                    'nombre_votes_maximum' => 5,
                ],
                'albums' => $albums,
            ]);
        });

    })->add(new JwtMiddleware());

};