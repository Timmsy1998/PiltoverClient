<?php

namespace Timmsy\PiltoverClient\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Timmsy\PiltoverClient\Contracts\RiotClientContract;
use Timmsy\PiltoverClient\Support\Routing;

class RiotClient implements RiotClientContract
{
    protected Client $platformHttp;
    protected Client $regionalHttp;

    public function __construct(
        string $apiKey,
        string $platform = "euw1",
        ?string $regional = null,
        string $platformTpl = "https://{platform}.api.riotgames.com",
        string $regionalTpl = "https://{regional}.api.riotgames.com",
        float $timeout = 10.0,
    ) {
        $regional = $regional ?: Routing::regionalFromPlatform($platform);

        $platformBase = Routing::host($platformTpl, "platform", $platform);
        $regionalBase = Routing::host($regionalTpl, "regional", $regional);

        $headers = [
            "Accept" => "application/json",
            "X-Riot-Token" => $apiKey,
            "User-Agent" => "PiltoverClient/1.0",
        ];

        $this->platformHttp = new Client([
            "base_uri" => $platformBase,
            "timeout" => $timeout,
            "headers" => $headers,
        ]);

        $this->regionalHttp = new Client([
            "base_uri" => $regionalBase,
            "timeout" => $timeout,
            "headers" => $headers,
        ]);
    }

    public function ping(): string
    {
        return "piltover-online";
    }

    public function accountByRiotId(string $gameName, string $tagLine): array
    {
        return $this->regionalGet(
            sprintf(
                "/riot/account/v1/accounts/by-riot-id/%s/%s",
                rawurlencode($gameName),
                rawurlencode($tagLine),
            ),
        );
    }

    public function summonerByPuuid(string $puuid): array
    {
        return $this->platformGet(
            "/lol/summoner/v4/summoners/by-puuid/" . rawurlencode($puuid),
        );
    }

    public function matchesByPuuid(string $puuid, array $params = []): array
    {
        return $this->regionalGet(
            "/lol/match/v5/matches/by-puuid/" . rawurlencode($puuid) . "/ids",
            [
                "query" => $params,
            ],
        );
    }

    public function matchById(string $matchId): array
    {
        return $this->regionalGet(
            "/lol/match/v5/matches/" . rawurlencode($matchId),
        );
    }

    /** @return array<string,mixed>|array<int,mixed> */
    protected function platformGet(string $path, array $options = []): array
    {
        return $this->decode($this->platformHttp->get($path, $options));
    }

    /** @return array<string,mixed>|array<int,mixed> */
    protected function regionalGet(string $path, array $options = []): array
    {
        return $this->decode($this->regionalHttp->get($path, $options));
    }

    /** @param \Psr\Http\Message\ResponseInterface $res */
    protected function decode($res): array
    {
        return json_decode(
            (string) $res->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
