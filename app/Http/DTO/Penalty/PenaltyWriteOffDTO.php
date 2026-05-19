<?php

namespace App\Http\DTO\Penalty;

class PenaltyWriteOffDTO {

    public int $client_id;
    public int $user_id;
    public int $trainer_id;
    public $description;

    /**
     * @return int
     */
    public function getClientId(): int {
        return $this->client_id;
    }

    /**
     * @param int $client_id
     */
    public function setClientId(int $client_id): void {
        $this->client_id = $client_id;
    }

    /**
     * @return int
     */
    public function getUserId(): int {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id): void {
        $this->user_id = $user_id;
    }

    /**
     * @return int
     */
    public function getTrainerId(): int {
        return $this->trainer_id;
    }

    /**
     * @param int $trainer_id
     */
    public function setTrainerId(int $trainer_id): void {
        $this->trainer_id = $trainer_id;
    }

    /**
     * @return mixed
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void {
        $this->description = $description;
    }
}
