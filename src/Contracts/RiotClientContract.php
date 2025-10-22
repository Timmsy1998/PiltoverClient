<?php

namespace Timmsy\PiltoverClient\Contracts;

interface RiotClientContract
{
    public function accountByRiotId(string $gameName, string $tagLine): array;
    public function summonerByPuuid(string $puuid): array;
    public function matchesByPuuid(string $puuid, array $params = []): array;
    public function matchById(string $matchId): array;
    public function ping(): string;
}
