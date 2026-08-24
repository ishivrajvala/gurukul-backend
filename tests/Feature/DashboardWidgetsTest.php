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
            ->assertSee('Leads')
            ->assertSee('Story submissions')
            ->assertSee('Applications')
            ->assertSee('Questions asked');
    }

    public function test_the_recent_submissions_table_renders(): void
    {
        Livewire::actingAs($this->panelUser())
            ->test(RecentSubmissions::class)
            ->assertSuccessful()
            ->assertSee('Latest leads');
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
        $user = $this->adminUser();
        $this->assertNotNull($user, 'no user to act as — seed one first');

        return $user;
    }

    /**
     * THE CHART RENDERS, AND ITS DATA ASSEMBLES.
     *
     * A chart widget is lazy, so the dashboard page returns 200 with only a placeholder where it
     * will be — which means the page-level sweep in `AdminPagesRenderTest` cannot see it and a
     * chart that throws would ship silently. This drives the component itself, which is the only
     * place `getData()` actually runs.
     */
    public function test_the_arrivals_chart_renders(): void
    {
        \Livewire\Livewire::actingAs($this->panelUser())
            ->test(\App\Filament\Admin\Widgets\ArrivalsChart::class)
            ->assertOk()
            ->assertSee('Who got in touch');
    }
}