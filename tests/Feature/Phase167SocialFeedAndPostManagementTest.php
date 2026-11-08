<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase167SocialFeedAndPostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_categories_and_tab_filtering(): void
    {
        $viewer = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();

        // Establish friendship
        Friendship::create([
            'user_id' => $viewer->id,
            'friend_id' => $friend->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
        Friendship::create([
            'user_id' => $friend->id,
            'friend_id' => $viewer->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $friendPost = Post::create([
            'user_id' => $friend->id,
            'content' => 'Post from my friend',
            'audience' => 'friends',
            'status' => 'published',
        ]);

        $publicPost = Post::create([
            'user_id' => $stranger->id,
            'content' => 'Public post from stranger',
            'audience' => 'public',
            'status' => 'published',
        ]);

        Sanctum::actingAs($viewer);

        // Test Friends Tab
        $responseFriends = $this->getJson('/api/v1/feed?tab=friends');
        $responseFriends->assertOk()
            ->assertJsonPath('success', true);

        $friendPostIds = collect($responseFriends->json('data'))->pluck('id')->all();
        $this->assertContains($friendPost->id, $friendPostIds);
        $this->assertNotContains($publicPost->id, $friendPostIds);

        // Test For You Tab (includes both)
        $responseForYou = $this->getJson('/api/v1/feed?tab=for_you');
        $responseForYou->assertOk();
        $forYouPostIds = collect($responseForYou->json('data'))->pluck('id')->all();
        $this->assertContains($friendPost->id, $forYouPostIds);
        $this->assertContains($publicPost->id, $forYouPostIds);
    }

    public function test_feed_block_mute_and_hide_filtering(): void
    {
        $viewer = User::factory()->create();
        $badUser = User::factory()->create();
        $mutedFriend = User::factory()->create();

        // Viewer mutes friend
        Friendship::create([
            'user_id' => $viewer->id,
            'friend_id' => $mutedFriend->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'is_muted' => true,
        ]);
        Friendship::create([
            'user_id' => $mutedFriend->id,
            'friend_id' => $viewer->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        $postBadUser = Post::create([
            'user_id' => $badUser->id,
            'content' => 'Annoying content',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $postMutedFriend = Post::create([
            'user_id' => $mutedFriend->id,
            'content' => 'Muted friend content',
            'audience' => 'public',
            'status' => 'published',
        ]);

        $normalPost = Post::create([
            'user_id' => User::factory()->create()->id,
            'content' => 'Normal post to hide',
            'audience' => 'public',
            'status' => 'published',
        ]);

        Sanctum::actingAs($viewer);

        // Block bad user
        $this->postJson("/api/v1/users/{$badUser->id}/block")->assertOk();

        // Hide normal post
        $this->postJson("/api/v1/posts/{$normalPost->id}/hide")->assertOk()
            ->assertJsonPath('data.is_hidden', true);

        // Verify feed excludes bad user post, muted friend post, and hidden post
        $feed = $this->getJson('/api/v1/feed?tab=for_you');
        $feed->assertOk();
        $feedIds = collect($feed->json('data'))->pluck('id')->all();

        $this->assertNotContains($postBadUser->id, $feedIds);
        $this->assertNotContains($postMutedFriend->id, $feedIds);
        $this->assertNotContains($normalPost->id, $feedIds);

        // Unhide post and verify it returns
        $this->postJson("/api/v1/posts/{$normalPost->id}/unhide")->assertOk()
            ->assertJsonPath('data.is_hidden', false);

        $feedAfterUnhide = $this->getJson('/api/v1/feed?tab=for_you');
        $feedAfterUnhideIds = collect($feedAfterUnhide->json('data'))->pluck('id')->all();
        $this->assertContains($normalPost->id, $feedAfterUnhideIds);
    }

    public function test_saved_posts_and_collections_lifecycle(): void
    {
        $viewer = User::factory()->create();
        $post1 = Post::create([
            'user_id' => $viewer->id,
            'content' => 'Recipe: Delicious Biryani',
            'audience' => 'public',
            'status' => 'published',
        ]);
        $post2 = Post::create([
            'user_id' => $viewer->id,
            'content' => 'Tech: Quantum Computing',
            'audience' => 'public',
            'status' => 'published',
        ]);

        Sanctum::actingAs($viewer);

        // 1. Save post1 to default 'All Saved Posts'
        $save1 = $this->postJson("/api/v1/posts/{$post1->id}/save");
        $save1->assertOk()
            ->assertJsonPath('data.is_saved', true)
            ->assertJsonPath('data.collection', 'All Saved Posts');

        // 2. Create custom collection 'Recipes'
        $createCol = $this->postJson('/api/v1/saved-posts/collections', [
            'name' => 'Recipes',
            'description' => 'My favorite cooking recipes',
        ]);
        $createCol->assertCreated()
            ->assertJsonPath('data.name', 'Recipes');
        $colId = $createCol->json('data.id');

        // 3. Add post1 to collection 'Recipes'
        $addToCol = $this->postJson("/api/v1/saved-posts/collections/{$colId}/add", [
            'post_id' => $post1->id,
        ]);
        $addToCol->assertOk();

        // 4. Save post2
        $this->postJson("/api/v1/posts/{$post2->id}/save")->assertOk();

        // 5. Query saved posts filtered by collection
        $recipeSaved = $this->getJson('/api/v1/saved-posts?collection=Recipes');
        $recipeSaved->assertOk();
        $this->assertCount(1, $recipeSaved->json('data'));
        $this->assertEquals($post1->id, $recipeSaved->json('data.0.id'));

        // 6. Query all collections
        $collectionsResp = $this->getJson('/api/v1/saved-posts/collections');
        $collectionsResp->assertOk();
        $colNames = collect($collectionsResp->json('data'))->pluck('name')->all();
        $this->assertContains('All Saved Posts', $colNames);
        $this->assertContains('Recipes', $colNames);

        // 7. Rename collection
        $renameResp = $this->putJson("/api/v1/saved-posts/collections/{$colId}", [
            'name' => 'Gourmet Recipes',
        ]);
        $renameResp->assertOk()
            ->assertJsonPath('data.name', 'Gourmet Recipes');

        // 8. Delete collection (items move to All Saved Posts)
        $this->deleteJson("/api/v1/saved-posts/collections/{$colId}")->assertOk();

        // 9. Unsave post
        $unsave = $this->deleteJson("/api/v1/posts/{$post1->id}/save");
        $unsave->assertOk()
            ->assertJsonPath('data.is_saved', false);
    }

    public function test_post_management_edit_and_delete_with_ownership(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();

        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Original text',
            'audience' => 'public',
            'status' => 'published',
        ]);

        // Attempt edit by unauthorized user
        Sanctum::actingAs($otherUser);
        $unauthEdit = $this->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Malicious edit',
        ]);
        $unauthEdit->assertForbidden();

        // Edit by author
        Sanctum::actingAs($author);
        $authEdit = $this->putJson("/api/v1/posts/{$post->id}", [
            'content' => 'Updated text by author',
            'audience' => 'friends',
        ]);
        $authEdit->assertOk()
            ->assertJsonPath('data.content', 'Updated text by author')
            ->assertJsonPath('data.audience', 'friends');

        // Attempt delete by unauthorized user
        Sanctum::actingAs($otherUser);
        $unauthDelete = $this->deleteJson("/api/v1/posts/{$post->id}");
        $unauthDelete->assertForbidden();

        // Delete by author
        Sanctum::actingAs($author);
        $authDelete = $this->deleteJson("/api/v1/posts/{$post->id}");
        $authDelete->assertOk();

        // Post is soft-deleted
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_reactions_and_nested_comments_flow(): void
    {
        $viewer = User::factory()->create();
        $post = Post::create([
            'user_id' => $viewer->id,
            'content' => 'Reaction and comment test post',
            'audience' => 'public',
            'status' => 'published',
        ]);

        Sanctum::actingAs($viewer);

        // 1. React with 'love'
        $react = $this->postJson("/api/v1/posts/{$post->id}/react", [
            'type' => 'love',
        ]);
        $react->assertOk()
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'love')
            ->assertJsonPath('data.likes_count', 1);

        // 2. Change reaction to 'care'
        $changeReact = $this->postJson("/api/v1/posts/{$post->id}/react", [
            'type' => 'care',
        ]);
        $changeReact->assertOk()
            ->assertJsonPath('data.reacted', true)
            ->assertJsonPath('data.type', 'care')
            ->assertJsonPath('data.likes_count', 1);

        // 3. Add top-level comment
        $commentResp = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Top level comment',
        ]);
        $commentResp->assertCreated();
        $commentId = $commentResp->json('data.id');

        // 4. Add nested reply to comment
        $replyResp = $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'body' => 'Nested reply',
            'parent_id' => $commentId,
        ]);
        $replyResp->assertCreated()
            ->assertJsonPath('data.parent_id', $commentId);

        // 5. React to comment
        $commentReact = $this->postJson("/api/v1/comments/{$commentId}/react", [
            'type' => 'like',
        ]);
        $commentReact->assertOk()
            ->assertJsonPath('data.reacted', true);

        // 6. Edit comment
        $editComment = $this->putJson("/api/v1/comments/{$commentId}", [
            'body' => 'Updated comment body',
        ]);
        $editComment->assertOk()
            ->assertJsonPath('data.body', 'Updated comment body');

        // 7. Delete comment
        $deleteComment = $this->deleteJson("/api/v1/comments/{$commentId}");
        $deleteComment->assertOk();
        $this->assertSoftDeleted('comments', ['id' => $commentId]);
    }
}
