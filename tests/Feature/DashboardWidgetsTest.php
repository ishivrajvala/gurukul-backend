<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Admin\Widgets\InboxOverview;
use App\Filament\Admin\Widgets\RecentSubmissions;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The dashboard widgets render, and say what they are supposed to say.
 *
 * FILAMENT WIDGETS ARE LIVEWIRE COMPONENTS, so none of their content is in the dashboard's initial
 * HTML — a page-level assertion sees a placeholder and passes whether or not the widget works. This
 * mounts them the way the browser does.
 *
 * The dashboard used to carry `AccountWidget` (who you are signed in as) and `FilamentInfoWidget`
 * (an advert for the framework). Neither answered the question this panel is opened to ask.
 */
class DashboardWidgetsTest extends TestCase
{
    public function test_the_inbox_overview_renders_every_stat(): void
    {
        Livewire::actingAs($this->panelUser())
            ->test(InboxOverview::class)
            ->assertSuccessful()
            ->assertSee('Circle requests')
            ->assertSee('Enquiries')
            ->assertSee('Story submissions')
            ->assertSee('Applications')
            ->assertSee('Questions asked');
    }

    public function test_the_recent_submissions_table_renders(): void
    {
        Livewire::actingAs($this->panelUser())
            ->test(RecentSubmissions::class)
            ->assertSuccessful()
            ->assertSee('Latest enquiries');
    }

    /**
     * The stock widgets are gone.
     *
     * Asserted rather than assumed: re-running `filament:install` or copying a panel provider from
     * another project puts them straight back, and an advert for the framework on the client's
     * dashboard is the sort of thing nobody reports and everybody sees.
     */
    public function test_the_stock_filament_widgets_are_not_registered(): void
    {
        $widgets = \Filament\Facades\Filament::getPanel('admin')->getWidgets();

        $this->assertNotContains(\Filament\Widgets\FilamentInfoWidget::class, $widgets);
        $this->assertNotContains(\Filament\Widgets\AccountWidget::class, $widgets);
        $this->assertContains(InboxOverview::class, $widgets);
    }

    private function panelUser(): User
    {
        $user = User::first();
        $this->assertNotNull($user, 'no user to act as — seed one first');

        return $user;
    }
}
