<?php

namespace App\Command;

use App\Service\VkApiClientWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * Class VkUserFollowersParserCommand
 * @package App\Command
 * @author  Andrey Zaytsev <dreyup96@gmail.com>
 */
class VkUserFollowersParserCommand extends Command
{
    private const FILES_PATH = 'public/files/';

    /**
     * @var string
     */
    protected static $defaultName = 'vk-user-followers:parse';

    /**
     * @var integer
     */
    protected $userId;

    /**
     * @var VkApiClientWrapper
     */
    protected $vkAPI;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * Configure command settings
     */
    protected function configure() :void
    {
        $this->setDescription('Get user followers by id from Vk API');
        $this->addOption('user-id', 'uid', InputOption::VALUE_REQUIRED, 'User id');
        $this->addOption('access-token', 'token', InputOption::VALUE_REQUIRED, 'User access token');
    }

    /**
     * Execute command for getting user followers
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws VKApiException
     * @throws VKClientException
     */
    protected function execute(InputInterface $input, OutputInterface $output) :int
    {
        $io = new SymfonyStyle($input, $output);

        $this->userId      = (int) $input->getOption('user-id');
        $this->accessToken = $input->getOption('access-token');

        try {
            if (!$this->userId) {
                throw new InvalidArgumentException('User id is required', 400);
            }

            if (!$this->accessToken) {
                throw new InvalidArgumentException('Access token is required', 400);
            }

            $io->title("Start parsing followers and friends of user with id {$this->userId}");
            $this->vkAPI = new VkApiClientWrapper($this->accessToken);

            $io->text('Start parsing followers');
            $followers = $this->vkAPI->getFollowers($this->userId);
            $io->text('Followers parsing done. Count of followers: ' . count($followers));

            $io->text('Start parsing friends');
            $friends = $this->vkAPI->getFriends($this->userId);
            $io->text('Friends parsing done. Count of friends: ' . count($friends));

            $io->text('Saving followers and friends ids into file...');
            $filename = $this->saveIdsIntoFile($this->userId, array_merge($followers, $friends));
            $io->success("Followers and friends were parsed and saved successfully into file {$filename}");

            return 1;
        } catch (\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return 0;
        }
    }

    /**
     *  Saving followers and friends ids into file
     *
     * @param int   $userId
     * @param array $followers
     * @return string
     */
    private function saveIdsIntoFile(int $userId, array $followers) :string
    {
        $filename = self::FILES_PATH . $userId . '|' . date('Y-m-d H:i:s');

        file_put_contents($filename, implode(',', $followers));

        return $filename;
    }

}
