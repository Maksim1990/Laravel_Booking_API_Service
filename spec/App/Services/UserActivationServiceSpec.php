<?php

namespace spec\App\Services;

use App\Enums\StatusEnum;

use App\Models\User;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use PhpSpec\ObjectBehavior;

class UserActivationServiceSpec extends ObjectBehavior
{
    private $mockUser;

    function let()
    {
        $this->mockUser = Mockery::mock(User::class, function (MockInterface $mock) {
            $mock->shouldReceive('save')->once();
        })->makePartial();

        $this->mockUser->fill([
            'name' => 'test',
            'email' => 'test@test.com',
            'status' => StatusEnum::PENDING->getStringValue()
        ]);
    }

    function it_can_activate_user()
    {
        $updatedUser = $this->changeUserStatus($this->mockUser, StatusEnum::ACTIVE);
        $updatedUser->status->shouldBe('active');
    }

    function it_can_deactivate_user()
    {
        $updatedUser = $this->changeUserStatus($this->mockUser, StatusEnum::DISABLED);
        $updatedUser->status->shouldBe('disabled');
    }
}
