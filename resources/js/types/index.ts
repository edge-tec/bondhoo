export interface User {
    id: number;
    name: string;
    username: string;
    email?: string;
    avatar_url?: string | null;
    cover_image_url?: string | null;
    bio?: string | null;
    is_verified?: boolean;
    friends_count?: number;
    followers_count?: number;
    following_count?: number;
}

export interface MediaItem {
    id: number;
    url?: string;
    original_path?: string;
    thumbnail_path?: string;
    mime_type?: string;
    width?: number;
    height?: number;
}

export interface CommentItem {
    id: number;
    post_id: number;
    parent_id?: number | null;
    body: string;
    likes_count: number;
    replies_count: number;
    created_at: string;
    user?: User;
    author?: User;
    user_has_liked?: boolean;
}

export interface PostItem {
    id: number;
    content: string;
    audience: 'public' | 'friends' | 'only_me';
    is_pinned?: boolean;
    likes_count?: number;
    comments_count?: number;
    shares_count?: number;
    counters?: {
        likes?: number;
        comments?: number;
        shares?: number;
    };
    created_at: string;
    author: User;
    media: MediaItem[];
    user_reaction?: string | null;
    viewer_reaction?: string | null;
    comments_disabled?: boolean;
    comments?: CommentItem[];
}


export interface NotificationItem {
    id: string;
    type: string;
    title?: string;
    message?: string;
    read_at?: string | null;
    created_at: string;
    data?: Record<string, any>;
}

export interface StoryMedia {
    id: number;
    url?: string;
    original_path?: string;
    type?: string;
    media_type?: string;
}

export interface Story {
    id: number;
    user_id: number;
    type: 'text' | 'media';
    content?: string;
    background_color?: string;
    font_family?: string;
    media?: StoryMedia[];
    story_media?: { id: number; media?: StoryMedia }[];
    views_count?: number;
    reactions_count?: number;
    user_reaction?: string | null;
    is_viewed?: boolean;
    created_at: string;
    expires_at?: string;
}

export interface UserStoryGroup {
    user: User;
    all_viewed: boolean;
    latest_story_time?: string;
    stories_count: number;
    stories: Story[];
}

export interface SuggestedFriend extends User {
    mutual?: string;
    mutual_count?: number;
    added?: boolean;
}

export interface UpcomingEvent {
    id: number;
    title: string;
    month: string;
    day: string;
    time_formatted: string;
    address: string;
    cover_image?: string;
}

export interface SharedProps {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
    };
}

export interface MarketplaceCategory {
    id: number;
    name: string;
    slug: string;
    icon?: string;
    display_order?: number;
    products_count?: number;
}

export interface MarketplaceSeller {
    id: number;
    name: string;
    username: string;
    avatar_url: string;
    rating: number;
    reviews_count: number;
    verified: boolean;
}

export interface MarketplaceReview {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    buyer?: {
        id: number;
        name: string;
        username: string;
        avatar_url: string;
    };
}

export interface MarketplaceProduct {
    id: number;
    title: string;
    description: string;
    price: number;
    currency: string;
    currency_symbol: string;
    formatted_price: string;
    condition: string;
    brand?: string | null;
    model?: string | null;
    is_negotiable: boolean;
    delivery_type: string;
    delivery_charge: number;
    location: string;
    status: string;
    images: string[];
    primary_image: string;
    is_boosted: boolean;
    is_featured: boolean;
    is_saved: boolean;
    views_count: number;
    time_ago: string;
    created_at?: string;
    category: MarketplaceCategory;
    seller: MarketplaceSeller;
    related_products?: MarketplaceProduct[];
    reviews?: MarketplaceReview[];
}

export interface PageItem {
    id: number;
    name: string;
    slug: string;
    username?: string;
    category?: string;
    bio?: string | null;
    description?: string | null;
    avatar_url?: string | null;
    cover_image_url?: string | null;
    followers_count: number;
    posts_count: number;
    is_verified?: boolean;
    status?: string;
    visibility?: string;
    is_following?: boolean;
    is_owner?: boolean;
    user_role?: string | null;
}

export interface ReelItem {
    id: number;
    user_id?: number;
    page_id?: number | null;
    page?: {
        id: number;
        name: string;
        slug: string;
        avatar_url?: string | null;
        is_verified?: boolean;
    } | null;
    user?: User;
    caption?: string | null;
    audio_title?: string | null;
    audio_artist?: string | null;
    duration?: number;
    views_count?: number;
    likes_count?: number;
    comments_count?: number;
    shares_count?: number;
    saves_count?: number;
    privacy?: string;
    video_url?: string;
    thumbnail_url?: string;
    cover_url?: string;
    has_liked?: boolean;
    my_reaction?: string | null;
    has_saved?: boolean;
    is_following?: boolean;
    allow_comments?: boolean;
    is_draft?: boolean;
    created_at?: string;
    formatted_time?: string;
}

export interface LiveStreamItem {
    id: number;
    title: string;
    description?: string | null;
    status: string;
    viewer_count?: number;
    user?: User;
    created_at?: string;
}
