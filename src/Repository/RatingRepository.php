<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class RatingRepository extends BaseRepository
{
    protected string $table = 'ratings';
    protected string $primaryKey = 'idRatings';
    protected array $columns = ['Grade', 'Albums_idAlbums'];

    public function findByAlbumId(int $albumId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM `ratings`
             WHERE `Albums_idAlbums` = :albumId
             ORDER BY `idRatings`'
        );

        $statement->execute(['albumId' => $albumId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRating(int $albumId, int $grade): int
    {
        $this->validateGrade($grade);

        return $this->insert([
            'Grade' => $grade . ' Star',
            'Albums_idAlbums' => $albumId,
        ]);
    }

    public function updateRating(int $id, int $grade): bool
    {
        $this->validateGrade($grade);

        return $this->update($id, [
            'Grade' => $grade . ' Star',
        ]);
    }

    public function deleteRating(int $id): bool
    {
        return $this->delete($id);
    }
    // Compare les moyennes de deux albums.
    public function compareAlbums(int $firstId, int $secondId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT
            a.idAlbums,
            a.Titre,
            COUNT(r.idRatings) AS nombre_votes,
            AVG(CAST(r.Grade AS UNSIGNED)) AS moyenne
         FROM albums a
         LEFT JOIN ratings r ON r.Albums_idAlbums = a.idAlbums
         WHERE a.idAlbums IN (:firstId, :secondId)
         GROUP BY a.idAlbums, a.Titre'
        );

        $statement->execute([
            'firstId' => $firstId,
            'secondId' => $secondId,
        ]);

        $albums = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (count($albums) !== 2) {
            return null;
        }

        foreach ($albums as &$album) {
            $album['idAlbums'] = (int) $album['idAlbums'];
            $album['nombre_votes'] = (int) $album['nombre_votes'];
            $album['moyenne'] = $album['moyenne'] !== null
                ? (float) $album['moyenne']
                : null;
        }
        unset($album);

        $winner = null;

        if ($albums[0]['moyenne'] === null || $albums[1]['moyenne'] === null) {
            $result = 'Comparaison impossible : un album ne possède aucune note.';
        } elseif ($albums[0]['moyenne'] === $albums[1]['moyenne']) {
            $result = 'Égalité';
        } else {
            $winner = $albums[0]['moyenne'] > $albums[1]['moyenne']
                ? $albums[0]['idAlbums']
                : $albums[1]['idAlbums'];

            $result = 'Le gagnant possède la meilleure moyenne.';
        }

        return [
            'albums' => $albums,
            'gagnant_id' => $winner,
            'resultat' => $result,
        ];
    }

// Pépites : moyenne >= 4/5 et entre 1 et 5 votes.
    public function findHiddenGems(): array
    {
        $statement = $this->db->query(
            'SELECT
            a.idAlbums,
            a.Titre,
            ar.Name AS artiste,
            COUNT(r.idRatings) AS nombre_votes,
            ROUND(AVG(CAST(r.Grade AS UNSIGNED)), 2) AS moyenne
         FROM albums a
         JOIN artists ar ON ar.idArtist = a.Artist_idArtist
         JOIN ratings r ON r.Albums_idAlbums = a.idAlbums
         GROUP BY a.idAlbums, a.Titre, ar.Name
         HAVING COUNT(r.idRatings) BETWEEN 1 AND 5
            AND AVG(CAST(r.Grade AS UNSIGNED)) >= 4
         ORDER BY moyenne DESC, nombre_votes ASC, a.idAlbums'
        );

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function validateGrade(int $grade): void
    {
        if ($grade < 1 || $grade > 5) {
            throw new \InvalidArgumentException(
                'La note doit être comprise entre 1 et 5.'
            );
        }
    }
}