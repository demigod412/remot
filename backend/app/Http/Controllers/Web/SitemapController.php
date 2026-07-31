<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use App\Models\SitePage;
use App\Models\Work;
use Illuminate\Http\Response;

/**
 * Generates an XML sitemap of the public, indexable URLs so search engines can
 * discover works, jobs, blog posts and content pages.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        // Key static pages.
        $statics = [
            'home'        => '1.0',
            'works.index' => '0.8',
            'jobs.index'  => '0.8',
            'featured'    => '0.7',
            'blog.index'  => '0.6',
            'contact'     => '0.4',
        ];
        foreach ($statics as $name => $priority) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                $urls[] = ['loc' => route($name), 'priority' => $priority];
            }
        }

        // Active works (slug pages).
        foreach (Work::active()->get(['slug', 'updated_at']) as $work) {
            if ($work->slug) {
                $urls[] = ['loc' => route('works.show', $work->slug), 'lastmod' => optional($work->updated_at)->toAtomString(), 'priority' => '0.6'];
            }
        }

        // Active job listings (slug pages).
        foreach (JobListing::where('status', 1)->get(['slug', 'updated_at']) as $job) {
            if ($job->slug) {
                $urls[] = ['loc' => route('jobs.show', $job->slug), 'lastmod' => optional($job->updated_at)->toAtomString(), 'priority' => '0.6'];
            }
        }

        // Blog posts.
        foreach (SitePage::where('tempname', 'blog')->get(['id', 'updated_at']) as $post) {
            $urls[] = ['loc' => route('blog.show', $post->id), 'lastmod' => optional($post->updated_at)->toAtomString(), 'priority' => '0.5'];
        }

        // Content pages (about, privacy, terms, …) rendered via the {slug} route.
        foreach (SitePage::where('tempname', '!=', 'blog')->whereNotNull('slug')->get(['slug', 'updated_at']) as $page) {
            if ($page->slug) {
                $urls[] = ['loc' => url($page->slug), 'lastmod' => optional($page->updated_at)->toAtomString(), 'priority' => '0.4'];
            }
        }

        return response()
            ->view('web.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
