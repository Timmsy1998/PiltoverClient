# Endpoint catalogue

Reviewed against [Riot's reference](https://developer.riotgames.com/apis/) on 2026-10-08.

Use named methods or `call(operationId, path, query, body)`. Query arrays are optional except where Riot requires a parameter. Tournament helpers take body then query; updates take code then body.

| Operation ID | PHP method | HTTP | Route | Authentication |
| --- | --- | --- | --- | --- |
| `account-v1.getByPuuid` | `accountByPuuid()` | GET `/riot/account/v1/accounts/by-puuid/{puuid}` | account | api |
| `account-v1.getByRiotId` | `accountByRiotId()` | GET `/riot/account/v1/accounts/by-riot-id/{gameName}/{tagLine}` | account | api |
| `account-v1.getByAccessToken` | `accountMe()` | GET `/riot/account/v1/accounts/me` | account | rso |
| `account-v1.getActiveShard` | `activeShard()` | GET `/riot/account/v1/active-shards/by-game/{game}/by-puuid/{puuid}` | account | api |
| `account-v1.getActiveRegion` | `accountRegion()` | GET `/riot/account/v1/region/by-game/{game}/by-puuid/{puuid}` | account | api |
| `champion-mastery-v4.getAllChampionMasteriesByPUUID` | `championMasteriesByPuuid()` | GET `/lol/champion-mastery/v4/champion-masteries/by-puuid/{encryptedPUUID}` | platform | api |
| `champion-mastery-v4.getChampionMasteryByPUUID` | `championMasteryByPuuid()` | GET `/lol/champion-mastery/v4/champion-masteries/by-puuid/{encryptedPUUID}/by-champion/{championId}` | platform | api |
| `champion-mastery-v4.getTopChampionMasteriesByPUUID` | `topChampionMasteriesByPuuid()` | GET `/lol/champion-mastery/v4/champion-masteries/by-puuid/{encryptedPUUID}/top` | platform | api |
| `champion-mastery-v4.getChampionMasteryScoreByPUUID` | `championMasteryScoreByPuuid()` | GET `/lol/champion-mastery/v4/scores/by-puuid/{encryptedPUUID}` | platform | api |
| `champion-v3.getChampionInfo` | `championRotations()` | GET `/lol/platform/v3/champion-rotations` | platform | api |
| `clash-v1.getPlayersByPUUID` | `clashPlayersByPuuid()` | GET `/lol/clash/v1/players/by-puuid/{puuid}` | platform | api |
| `clash-v1.getTeamById` | `clashTeamById()` | GET `/lol/clash/v1/teams/{teamId}` | platform | api |
| `clash-v1.getTournaments` | `clashTournaments()` | GET `/lol/clash/v1/tournaments` | platform | api |
| `clash-v1.getTournamentByTeam` | `clashTournamentByTeam()` | GET `/lol/clash/v1/tournaments/by-team/{teamId}` | platform | api |
| `clash-v1.getTournamentById` | `clashTournamentById()` | GET `/lol/clash/v1/tournaments/{tournamentId}` | platform | api |
| `league-exp-v4.getLeagueEntries` | `leagueEntriesExpanded()` | GET `/lol/league-exp/v4/entries/{queue}/{tier}/{division}` | platform | api |
| `league-v4.getChallengerLeague` | `challengerLeague()` | GET `/lol/league/v4/challengerleagues/by-queue/{queue}` | platform | api |
| `league-v4.getLeagueEntriesByPUUID` | `leagueEntriesByPuuid()` | GET `/lol/league/v4/entries/by-puuid/{encryptedPUUID}` | platform | api |
| `league-v4.getLeagueEntries` | `leagueEntries()` | GET `/lol/league/v4/entries/{queue}/{tier}/{division}` | platform | api |
| `league-v4.getGrandmasterLeague` | `grandmasterLeague()` | GET `/lol/league/v4/grandmasterleagues/by-queue/{queue}` | platform | api |
| `league-v4.getMasterLeague` | `masterLeague()` | GET `/lol/league/v4/masterleagues/by-queue/{queue}` | platform | api |
| `lol-challenges-v1.getAllChallengeConfigs` | `challengeConfigs()` | GET `/lol/challenges/v1/challenges/config` | platform | api |
| `lol-challenges-v1.getAllChallengePercentiles` | `challengePercentiles()` | GET `/lol/challenges/v1/challenges/percentiles` | platform | api |
| `lol-challenges-v1.getChallengeConfigs` | `challengeConfig()` | GET `/lol/challenges/v1/challenges/{challengeId}/config` | platform | api |
| `lol-challenges-v1.getChallengeLeaderboards` | `challengeLeaderboard()` | GET `/lol/challenges/v1/challenges/{challengeId}/leaderboards/by-level/{level}` | platform | api |
| `lol-challenges-v1.getChallengePercentiles` | `challengePercentilesById()` | GET `/lol/challenges/v1/challenges/{challengeId}/percentiles` | platform | api |
| `lol-challenges-v1.getPlayerData` | `challengePlayerData()` | GET `/lol/challenges/v1/player-data/{puuid}` | platform | api |
| `lol-rso-match-v1.getMatchIds` | `rsoMatchIds()` | GET `/lol/rso-match/v1/matches/ids` | regional | rso |
| `lol-rso-match-v1.getMatch` | `rsoMatchById()` | GET `/lol/rso-match/v1/matches/{matchId}` | regional | rso |
| `lol-rso-match-v1.getTimeline` | `rsoMatchTimeline()` | GET `/lol/rso-match/v1/matches/{matchId}/timeline` | regional | rso |
| `lol-status-v4.getPlatformData` | `platformStatus()` | GET `/lol/status/v4/platform-data` | platform | api |
| `match-v5.getMatchIdsByPUUID` | `matchesByPuuid()` | GET `/lol/match/v5/matches/by-puuid/{puuid}/ids` | regional | api |
| `match-v5.getReplay` | `replaysByPuuid()` | GET `/lol/match/v5/matches/by-puuid/{puuid}/replays` | regional | api |
| `match-v5.getMatch` | `matchById()` | GET `/lol/match/v5/matches/{matchId}` | regional | api |
| `match-v5.getTimeline` | `matchTimeline()` | GET `/lol/match/v5/matches/{matchId}/timeline` | regional | api |
| `spectator-v5.getCurrentGameInfoByPuuid` | `activeGameByPuuid()` | GET `/lol/spectator/v5/active-games/by-summoner/{encryptedPUUID}` | platform | api |
| `summoner-v4.getByPUUID` | `summonerByPuuid()` | GET `/lol/summoner/v4/summoners/by-puuid/{encryptedPUUID}` | platform | api |
| `summoner-v4.getByAccessToken` | `summonerMe()` | GET `/lol/summoner/v4/summoners/me` | platform | rso |
| `tournament-stub-v5.createTournamentCode` | `createTournamentStubCodes()` | POST `/lol/tournament-stub/v5/codes` | tournament | api |
| `tournament-stub-v5.getTournamentCode` | `tournamentStubCode()` | GET `/lol/tournament-stub/v5/codes/{tournamentCode}` | tournament | api |
| `tournament-stub-v5.getLobbyEventsByCode` | `tournamentStubLobbyEvents()` | GET `/lol/tournament-stub/v5/lobby-events/by-code/{tournamentCode}` | tournament | api |
| `tournament-stub-v5.registerProviderData` | `registerTournamentStubProvider()` | POST `/lol/tournament-stub/v5/providers` | tournament | api |
| `tournament-stub-v5.registerTournament` | `registerTournamentStub()` | POST `/lol/tournament-stub/v5/tournaments` | tournament | api |
| `tournament-v5.createTournamentCode` | `createTournamentCodes()` | POST `/lol/tournament/v5/codes` | tournament | tournament |
| `tournament-v5.getTournamentCode` | `tournamentCode()` | GET `/lol/tournament/v5/codes/{tournamentCode}` | tournament | tournament |
| `tournament-v5.updateCode` | `updateTournamentCode()` | PUT `/lol/tournament/v5/codes/{tournamentCode}` | tournament | tournament |
| `tournament-v5.getGames` | `tournamentGames()` | GET `/lol/tournament/v5/games/by-code/{tournamentCode}` | tournament | tournament |
| `tournament-v5.getLobbyEventsByCode` | `tournamentLobbyEvents()` | GET `/lol/tournament/v5/lobby-events/by-code/{tournamentCode}` | tournament | tournament |
| `tournament-v5.registerProviderData` | `registerTournamentProvider()` | POST `/lol/tournament/v5/providers` | tournament | tournament |
| `tournament-v5.registerTournament` | `registerTournament()` | POST `/lol/tournament/v5/tournaments` | tournament | tournament |
