<?php

namespace PierreMiniggio\TiktokToShorts\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class ShortsValueForTikTokVideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function insertOrUpdateField(
        int $tiktokVideoId,
        string $fieldName,
        string $fieldValue
    ): void
    {
        $valueQueryParams = [
            'tiktok_id' => $tiktokVideoId,
            'field_name' => $fieldName
        ];

        $findValueQuery = [
            $this->fetcher
                ->createQuery('shorts_values_for_tiktok_video')
                ->select('id')
                ->where('tiktok_id = :tiktok_id AND field_name = :field_name')
            ,
            $valueQueryParams
        ];
        $queriedIds = $this->fetcher->query(...$findValueQuery);
        
        if (! $queriedIds) {
            $insertParams = [
                ...$valueQueryParams,
                'field_value' => $fieldValue
            ];

            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('shorts_values_for_tiktok_video')
                    ->insertInto(
                        'tiktok_id, field_name, field_value',
                        ':tiktok_id, :field_name, : field_value'
                    )
                ,
                $insertParams
            );
            
            return;
        }

        $valueId = (int) $queriedIds[0]['id'];

        if (! $valueId) {
            throw new \Exception('Error: no value id');
        }
        
        $this->fetcher->exec(
            $this->fetcher
                ->createQuery('shorts_values_for_tiktok_video')
                ->update('field_value = :field_value')
                ->where('id = :id')
            ,
            [
                'id' => $valueId,
                'field_value' => $fieldValue
            ]
        );
    }
}
