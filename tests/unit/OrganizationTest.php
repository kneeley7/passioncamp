<?php

use App\Order;
use App\Organization;
use App\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::find(8);
    }

    // public function test_fetch_setting()
    // {
    //     $setting = $this->organization->setting('stripe_access_token');

    //     $this->assertEquals('sk_test_LFUl1Vgp2RHZJ7TiB17Tg55r', $setting);
    // }

    public function test_add_setting1()
    {
        $this->markTestSkipped();
        $this->organization->setting('stripe_access_token', '111');

        $setting = $this->organization->setting('stripe_access_token');

        $this->assertEquals('111', $setting);
    }

    // public function test_add_setting2()
    // {
    //     $this->organization->setting(['stripe_access_token' => '111']);

    //     $setting = $this->organization->setting('stripe_access_token');

    //     $this->assertEquals('111', $setting);
    // }

    /** @test */
    public function it_gets_relations()
    {
        $this->markTestIncomplete();
        $organization = factory(Organization::class)->create();

        $orders = $organization->orders()->saveMany(
            factory(Order::class, 10)->make(['organization_id' => null])
        );

        $orders->first()->tickets()->saveMany(
            factory(Ticket::class, 10)->make()
        );

        $organization2 = factory(Organization::class)->create();

        $orders2 = $organization2->orders()->saveMany(
            factory(Order::class, 8)->make(['organization_id' => null])
        );

        $orders2->first()->tickets()->saveMany(
            factory(Ticket::class, 8)->make()
        );

        \App\OrderItem::create([
            'owner_type' => Organization::class,
            'owner_id' => 1,
            // 'type' => 'ticket'
        ]);

        $this->assertCount(2, Organization::all());
        $this->assertCount(18, Order::all());
        $this->assertCount(18, Ticket::all());
        $this->assertCount(10, $orders->first()->tickets()->get());
        $this->assertCount(10, Ticket::forOrganization($organization)->get());
        $this->assertCount(10, $organization->attendees()->get());
    }
}
