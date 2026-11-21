<?php

namespace PierreMiniggio\TiktokToShorts\Tests\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\TiktokToShorts\Repository\LinkedChannelRepository;

class LinkedChannelRepositoryTest extends TestCase
{
    /** @var DatabaseConnection&MockObject */
    private DatabaseConnection $connection;
    private LinkedChannelRepository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnection::class);
        $this->repository = new LinkedChannelRepository(new DatabaseFetcher($this->connection));
    }

    public function testFindActiveReturnsTheLinkedChannelsFromTheDatabase(): void
    {
        $channels = [
            ['t_id' => 1, 's_id' => 2, 'youtube_id' => 'abc', 'description' => 'A channel'],
        ];
        $this->connection->method('query')->willReturn($channels);

        self::assertSame($channels, $this->repository->findActive());
    }

    public function testFindActiveStillAsksForTheLinkedShortsAndYoutubeChannels(): void
    {
        $sql = $this->querySentByFindActive();

        self::assertStringContainsString('FROM shorts_channel_tiktok_account as scta', $sql);
        self::assertStringContainsString('JOIN shorts_channel as s ON s.id = scta.shorts_id', $sql);
        self::assertStringContainsString('JOIN youtube_channel as y ON s.youtube_id = y.id', $sql);
        self::assertStringContainsString('scta.tiktok_id as t_id', $sql);
        self::assertStringContainsString('s.id as s_id', $sql);
        self::assertStringContainsString('y.youtube_id', $sql);
    }

    public function testFindActiveIgnoresChannelsLinkedToARetiredTikTokAccount(): void
    {
        $sql = $this->querySentByFindActive();

        self::assertStringContainsString('JOIN tiktok_account as ta ON ta.id = scta.tiktok_id', $sql);
        self::assertStringEndsWith('WHERE ta.retired_at IS NULL', $sql);
    }

    private function querySentByFindActive(): string
    {
        $sentSql = null;
        $this->connection
            ->expects(self::once())
            ->method('query')
            ->willReturnCallback(function (string $sql) use (&$sentSql): array {
                $sentSql = trim(preg_replace('/\s+/', ' ', $sql));

                return [];
            })
        ;

        $this->repository->findActive();

        self::assertNotNull($sentSql);

        return $sentSql;
    }
}
