# Connectly Android — Quality Assurance & Testing Strategy

**Testing Paradigm:** Automated Multi-Tiered Testing Pyramid  
**Target Coverage:** > 80% Unit & Integration Test Coverage across Core & Domain Modules  
**Continuous Integration (CI):** GitHub Actions Automated Test & Lint Pipeline

---

## 1. Testing Pyramid & Target Distribution

```
                ┌───────────────┐
                │   E2E & UI    │  10% (Maestro / Compose Test)
                │  Regressions  │
             ┌──┴───────────────┴──┐
             │  Integration Tests  │  20% (Room in-memory, MockWebServer)
          ┌──┴─────────────────────┴──┐
          │     Unit Tests (Pure)     │  70% (ViewModels, UseCases, Repositories)
          └───────────────────────────┘
```

---

## 2. Unit Testing Strategy (70%)

### 2.1. ViewModels & MVI Intent Testing
- **Tools:** JUnit 5, MockK, Turbine, Kotlinx Coroutines Test (`StandardTestDispatcher`).
- **Focus:** Ensure every UI intent dispatches the expected state transitions and one-shot effects.

```kotlin
// Example: FeedViewModelTest.kt
@OptIn(ExperimentalCoroutinesApi::class)
class FeedViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getFeedUseCase: GetFeedUseCase = mockk()
    private val toggleReactionUseCase: TogglePostReactionUseCase = mockk()
    private val getStoriesTrayUseCase: GetStoriesTrayUseCase = mockk()

    private lateinit var viewModel: FeedViewModel

    @BeforeEach
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        coEvery { getFeedUseCase(any()) } returns flowOf(PagingData.empty())
        coEvery { getStoriesTrayUseCase() } returns flowOf(emptyList())
        viewModel = FeedViewModel(getFeedUseCase, toggleReactionUseCase, getStoriesTrayUseCase)
    }

    @AfterEach
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when LikePost intent received, optimism is reflected and use case invoked`() = runTest {
        coEvery { toggleReactionUseCase(postId = 101L, "like") } returns Result.success(Unit)

        viewModel.handleIntent(FeedUiIntent.LikePost(101L))
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify(exactly = 1) { toggleReactionUseCase(101L, "like") }
    }
}
```

### 2.2. Domain Use Cases & Data Mappers
- Pure Kotlin unit tests validating mapping between Network DTOs, Room Entities, and Domain Models with 100% code branch coverage.

---

## 3. Integration Testing Strategy (20%)

### 3.1. Room Database & DAO In-Memory Testing
- **Tools:** `Room.inMemoryDatabaseBuilder`, AndroidX Test Runner.
- **Focus:** Ensure data persists, updates cascade properly, foreign key constraints are honored, and reactive `Flow` queries emit on change.

```kotlin
@RunWith(AndroidJUnit4::class)
class MessageDaoTest {

    private lateinit var database: ConnectlyDatabase
    private lateinit var messageDao: MessageDao
    private lateinit var conversationDao: ConversationDao

    @Before
    fun initDb() {
        val context = ApplicationProvider.getApplicationContext<Context>()
        database = Room.inMemoryDatabaseBuilder(context, ConnectlyDatabase::class.java)
            .allowMainThreadQueries()
            .build()
        messageDao = database.messageDao()
        conversationDao = database.conversationDao()
    }

    @After
    fun closeDb() {
        database.close()
    }

    @Test
    fun insertAndRetrieve5StateMessages() = runTest {
        val conversation = ConversationEntity(id = 1L, type = "direct", ...)
        conversationDao.insert(conversation)

        val message = MessageEntity(
            id = 501L,
            conversationId = 1L,
            senderId = 2L,
            senderName = "Sadia Islam",
            body = "Hello Connectly!",
            type = "text",
            clientMessageId = "msg_uuid_123",
            deliveryStatus = "sending",
            isMine = true,
            createdAt = System.currentTimeMillis(),
            sentAt = null,
            deliveredAt = null,
            readAt = null
        )

        messageDao.insert(message)

        val retrieved = messageDao.getMessageByClientMessageId("msg_uuid_123")
        assertNotNull(retrieved)
        assertEquals("sending", retrieved?.deliveryStatus)

        // Update to sent
        messageDao.updateDeliveryStatus(501L, "sent", System.currentTimeMillis())
        val updated = messageDao.getMessageById(501L)
        assertEquals("sent", updated?.deliveryStatus)
    }
}
```

### 3.2. Network & Retrofit Testing with MockWebServer
- Simulates server response scenarios: HTTP 200 Success, HTTP 201 Created with client idempotency, HTTP 401 Unauthorized (triggering token refresh), HTTP 422 Validation Error, and HTTP 500 Server Failure.

---

## 4. UI & Jetpack Compose Testing (10%)

### 4.1. Compose UI Component Testing
- Tests isolation of UI elements (`ConnectlyTopBar`, `PostCard`, `ReelsPlayerOverlay`, `MessengerBubble`) using `createComposeRule()`:

```kotlin
@RunWith(AndroidJUnit4::class)
class PostCardTest {

    @get:Rule
    val composeTestRule = createComposeRule()

    @Test
    fun postCard_displaysAuthorAndContent_andTriggersLike() {
        var liked = false
        val samplePost = PostItem(
            id = 1L,
            authorName = "Afrin Akter",
            content = "Life is better when you're surrounded by good people. ❤️",
            reactionsCount = 2400,
            commentsCount = 248
        )

        composeTestRule.setContent {
            PostCard(post = samplePost, onLikeClick = { liked = true })
        }

        composeTestRule.onNodeWithText("Afrin Akter").assertIsDisplayed()
        composeTestRule.onNodeWithText("Life is better when you're surrounded by good people. ❤️").assertIsDisplayed()
        composeTestRule.onNodeWithContentDescription("Like").performClick()

        assertTrue(liked)
    }
}
```

### 4.2. Visual Regression / Screenshot Testing
- Uses **Roborazzi** to capture Compose previews and compare pixel differences against baseline reference screenshots across Light & Dark themes.

---

## 5. End-to-End (E2E) Critical User Flows (Maestro / Espresso)

The following 5 golden user journeys are continuously validated on physical and virtual devices:

1. **Flow 1: User Authentication & Feed Exploration**
   - Launch App -> Enter Credentials -> Verify 2FA -> Land on Home Feed -> Switch Tabs (`For You` -> `Following`) -> Pull to Refresh.
2. **Flow 2: Story Viewing & Reaction**
   - Click First Story in Tray -> Story Viewer Opens -> Segment Bar Advances -> Tap Heart Reaction -> Send Reply Message -> Auto-advance to next story.
3. **Flow 3: Reels Swipe & Engagement**
   - Navigate to Reels Tab -> Video Plays Automatically -> Tap Like (Heart Animates, Counter increments) -> Tap Comments (Comments Drawer slides up) -> Post Comment -> Swipe to Next Reel.
4. **Flow 4: Messenger 5-State Conversation**
   - Open Messenger -> Select "Sadia Islam" -> Type "Meeting tomorrow?" -> Tap Send (Renders `⌛ sending` optimistically) -> Receives HTTP 201 (Transitions to `✓ sent`) -> Emulates delivery receipt (`✓✓ delivered`).
5. **Flow 5: Audio/Video Call Journey**
   - In Chat -> Tap Video Call Icon -> Full Screen Call Screen Opens -> Emulates Callee Accept -> Media Stream Connects -> Toggle Mute -> End Call.

---

## 6. Performance Benchmarks & Leak Detection

1. **Jetpack Macrobenchmark:**
   - **Cold Startup Target:** `< 800ms` on mid-range hardware (e.g., Pixel 6a / Galaxy A54).
   - **Frame Timing:** `< 5%` jank frames during fast fling scrolling on Home Feed and Reels.
2. **LeakCanary in Debug Builds:**
   - Detects retained Activities, ViewModels, or ExoPlayer instances immediately during QA testing.
3. **Network & Battery Profiling:**
   - WorkManager uses `NetworkType.CONNECTED` and `BatteryNotLow` constraints for background cache cleanups.
