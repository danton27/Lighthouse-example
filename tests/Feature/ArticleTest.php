<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function can_get_author_from_articles()
    {
        $userA = factory(User::class)->create(['name' => 'A']);
        $userB = factory(User::class)->create(['name' => 'B']);
        $userC = factory(User::class)->create(['name' => 'C']);
        $articleA = factory(Article::class)->create(['author_id' => $userA->id]);
        $articleB = factory(Article::class)->create(['author_id' => $userB->id]);
        $articleC = factory(Article::class)->create(['author_id' => $userC->id]);

        $response = $this->graphql("{articles(first: 10) { edges { node { author { name } } } } }");

        $authorName = $response->json("data.articles.edges.*.node.author.name");
        $this->assertCount(3, $authorName);
        $this->assertArraySubset(
            $authorName,
            [
                $userA->name,
                $userB->name,
                $userC->name
            ]
        );
    }

    /** @test */
    public function can_get_title_from_article()
    {
        $articleA = factory(Article::class)->create(['title' => "What a great article"]);
        $articleB = factory(Article::class)->create(['title' => "What a bad article"]);
        $articleC = factory(Article::class)->create(['title' => "What a average article"]);

        $response = $this->graphql("{articles(first: 10) { edges { node { title } } } }");

        $titles = $response->json("data.articles.edges.*.node.title");
        $this->assertCount(3, $titles);
        $this->assertArraySubset(
            $titles,
            [
                $articleA->title,
                $articleB->title,
                $articleC->title,
            ]
        );
    }

    /** @test */
    public function can_get_body_from_article()
    {
        $articleA = factory(Article::class)->create(['body' => "my lorem body"]);
        $articleB = factory(Article::class)->create(['body' => "My lorem ipsum body"]);
        $articleC = factory(Article::class)->create(['body' => "Just another body"]);

        $response = $this->graphql("{articles(first: 10) { edges { node { body } } } }");

        $bodies = $response->json("data.articles.edges.*.node.body");
        $this->assertCount(3, $bodies);
        $this->assertArraySubset(
            $bodies,
            [
                $articleA->body,
                $articleB->body,
                $articleC->body,
            ]
        );
    }

    /** @test */
    public function can_get_id_from_article()
    {
        $articleA = factory(Article::class)->create(['id' => 1]);
        $articleB = factory(Article::class)->create(['id' => 100]);
        $articleC = factory(Article::class)->create(['id' => 999]);

        $response = $this->graphql("{articles(first: 10) { edges { node { id } } } }");

        $ids = $response->json("data.articles.edges.*.node.id");
        $ids = $this->mapGlobalIdToId($ids, "Article");

        $this->assertCount(3, $ids);
        $this->assertArraySubset(
            $ids,
            [
                $articleA->id,
                $articleB->id,
                $articleC->id,
            ]
        );
    }

    public function mapGlobalIdToId($ids, $type = null)
    {
        return collect($ids)->map(function ($id) use ($type) {
            $id = explode(':', base64_decode($id));
            if(!is_null($type)) {
                $this->assertEquals($type, $id[0]);
            }
            return $id[1];
        })->all();
    }

    public function graphql(string $query)
    {
        return $this->post('/graphql', [
            'query' => $query
        ]);
    }
}
