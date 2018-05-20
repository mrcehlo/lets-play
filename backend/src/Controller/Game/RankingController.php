<?php
declare(strict_types=1);

namespace Mleko\LetsPlay\Controller\Game;


use Mleko\LetsPlay\Entity\Bet;
use Mleko\LetsPlay\Entity\Match;
use Mleko\LetsPlay\Http\Response;
use Mleko\LetsPlay\Logic\ScoreCalculator;
use Mleko\LetsPlay\Repository\BetsRepository;
use Mleko\LetsPlay\Repository\GameRepository;
use Mleko\LetsPlay\Repository\MatchSetRepository;
use Mleko\LetsPlay\Repository\UserRepository;

class RankingController
{
    /** @var GameRepository */
    private $gameRepository;
    /** @var MatchSetRepository */
    private $matchSetRepository;
    /** @var BetsRepository */
    private $betRepository;
    /** @var UserRepository */
    private $userRepository;

    /**
     * RankingController constructor.
     * @param GameRepository $gameRepository
     * @param MatchSetRepository $matchSetRepository
     * @param BetsRepository $betRepository
     * @param UserRepository $userRepository
     */
    public function __construct(GameRepository $gameRepository, MatchSetRepository $matchSetRepository, BetsRepository $betRepository, UserRepository $userRepository) {
        $this->gameRepository = $gameRepository;
        $this->matchSetRepository = $matchSetRepository;
        $this->betRepository = $betRepository;
        $this->userRepository = $userRepository;
    }

    public function getRanking($gameId) {
        $game = $this->gameRepository->getGame($gameId);
        $set = $this->matchSetRepository->getSet($game->getMatchSetId()->getUuid());
        $bets = $this->betRepository->getGameBets($game->getId());

        $calculator = new ScoreCalculator();
        $ranking = $this->buildRanking($set->getMatches(), $bets, $calculator);

        $users = $this->userRepository->getMany(\array_map(function ($entry) {
            return $entry["userId"];
        }, $ranking));
        $keyedUsers = [];
        foreach ($users as $user) {
            $keyedUsers[$user->getId()->getUuid()] = $user;
        }
        $ranking = \array_map(function ($entry) use ($keyedUsers) {
            $entry["user"] = $keyedUsers[$entry["userId"]->getUuid()];
            return $entry;
        }, $ranking);

        return new Response($ranking);
    }

    /**
     * @param Match[] $matches
     * @param Bet[] $bets
     * @param ScoreCalculator $calculator
     * @return array
     */
    private function buildRanking($matches, $bets, $calculator): array {
        $ranking = [];
        $keyedMatches = [];
        foreach ($matches as $match) {
            $keyedMatches[$match->getId()->getUuid()] = $match;
        }
        foreach ($bets as $bet) {
            $matchResult = isset($keyedMatches[$bet->getMatchId()->getUuid()]) ? $keyedMatches[$bet->getMatchId()->getUuid()]->getResult() : null;
            if (null === $matchResult) {
                continue;
            }
            $points = $calculator->calculateScore($matchResult, $bet->getBet());
            if (!isset($ranking[$bet->getUserId()->getUuid()])) {
                $ranking[$bet->getUserId()->getUuid()] = ["userId" => $bet->getUserId(), "points" => 0];
            }
            $ranking[$bet->getUserId()->getUuid()]["points"] += $points;
        }

        \usort($ranking, function ($a, $b) {
            return $a["points"] - $b["points"];
        });

        return \array_values($ranking);
    }
}
