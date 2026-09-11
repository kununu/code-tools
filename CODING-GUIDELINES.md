# Coding guidelines

## Patterns

- As a rule of thumb, follow SOLID principles (some examples [here](https://dev.to/dalelantowork/solid-principles-object-oriented-programming-in-php-3p3e))

## General

- Use strict types
  - `declare(strict_types=1);` in every PHP file.
- Use descriptive names for classes and variables.
- In development environment, you should call the code fixer when you are committing your changes.
- Promoted properties are preferred
  - If these decrease the legibility of the code then stick to "classic" properties and add the attributes there
- Order of elements of a class (this also **applies to test classes**):
  - Traits uses first (alphabetically)
  - Constants
    - public
    - protected
    - private
  - Properties
    - public
    - protected
    - private
  - Constructor (no matter what visibility)
    - The constructor must **ALWAYS** be the first method even if it is not public
  - Methods
    - public static
    - public
    - protected static
    - protected
    - private static
    - private
  - When using promoted properties we are more relaxed, but ideally try to keep the visibility order in the constructor
    - If possible put all the promoted properties first ordered by visibility and "regular" constructor parameters at the end

  ###### EXAMPLE
    ```php
    final readonly class MyClass
    {
        use FirstTrait;
        use SecondTrait;

        public const string HELLO = 'hello';

        protected const string WORLD = 'world';
        protected const string EUROPE = 'europe';

        private const int VALUE = 9000;

        public float $score;

        // Yeah... it does not make sense to be protected as the class is final :)
        // But on some cases it can be useful ;)
        protected string $name;

        private string $whatever;
        private int $value;
        private AnotherClass $anotherClass;

        private function __construct(
            public int $age,
            protected float $salary,
            string $name
        ) {
            $this->name = $name;
            $this->score = $this->calculate();
        }

        public static function create(): self
        {
            return new self(self::europe());
        }

        public function name(): string
        {
            return $this->name;
        }

        protected static function hello(): void
        {
        }

        protected function world(): string
        {
            return self::WORLD;
        }

        private static function europe(): string
        {
            return self::EUROPE;
        }

        private function calculate(): float
        {
            return ($this->salary + self::VALUE) * $this->age;
        }
    }
    ```
- Do not use PHPDoc annotations unless it's an absolute necessity (e.g. to signal that methods are throwing exceptions)
  - Most libraries/bundles now switch to using PHP attributes, so this should be the preferred notation.
  - `@method` annotations are fine for dynamic methods
  - Example for PHPUnit tests:
  ###### DO
    ```php
    #[DataProvider('myDataProvider')]
    ```
  ###### DON'T
    ```php
    /** @dataProvider myDataProvider */
    ```
- Use one-line comments when the comment has only one line.
  ###### DO
    ```php
    /** @throws Exception1|Exception2 */
    ```
  ###### DON'T
    ```php
    /**
    * @throws Exception1
    * @throws Exception2
    */
    ```
- Place method parameters in multiple lines only if they don’t fit in one line (valid for constructors and other methods).
  ###### DO
    ```php
    public function __construct(private string $profileUuid)
    {
    }
    ```
  ###### DON'T
    ```php
    public function __construct(
        private string $profileUuid
    ) {
    }
    ```
- When a call's arguments **do** span multiple lines (e.g. because one of them is a multi-line array), place **every** argument on its own line. Don't keep the first argument on the opening line while the rest wrap.
  ###### DO
    ```php
    $this->logger->warning(
        'Failed to do something',
        [
            'message' => $exception->getMessage(),
            'uuid'    => $object->uuid,
        ]
    );
    ```
  ###### DON'T
    ```php
    $this->logger->warning('Failed to do something', [
        'message' => $exception->getMessage(),
        'uuid'    => $object->uuid,
    ]);
    ```
- Type your constants
  ###### DO
    ```php
    private const string MY_CONSTANT = 'value';
    ```
  ###### DON´T
    ```php
    private const MY_CONSTANT = 'value';
    ```
- Use constants instead of methods and variables to store static values .
  ###### DO
    ```php
    private const FIELDS = ['industry_id', 'location', 'name'];
    ```
  ###### DON´T
    ```php
    private array $fields = ['industry_id', 'location', 'name'];

    private function fields(): array
    {
      return ['industry_id', 'location', 'name'];
    }
    ```
- Use PHP Enumerations instead of constants if you need a fixed set of allowed values.
  ###### DO
    ```php
    enum MyEnum: string
    {
        case Excellent = 'excellent';
        case Good = 'good';
        case Regular = 'regular';
        case Mediocre = 'mediocre';
        case Bad = 'bad';
    }

    final readonly class MyClass
    {
        private MyEnum $value;

        public function __construct(string $value)
        {
            $this->value = MyEnum::from($value);
        }
    }
    ```
  ###### DON´T
    ```php
    final readonly class MyClass
    {
        private const string EXCELLENT = 'excellent';
        private const string GOOD = 'good';
        private const string REGULAR = 'regular';
        private const string MEDIOCRE = 'mediocre';
        private const string BAD = 'bad';

        private const array ALLOWED_VALUES = [
            self::EXCELLENT,
            self::GOOD,
            self::REGULAR,
            self::MEDIOCRE,
            self::BAD,
        ];

        private string $value;

        public function __construct(string $value)
        {
            if (!in_array($value, self::ALLOWED_VALUES)) {
                throw new InvalidArgumentException(sprintf('Invalid value "%s', $value));
            }
            $this->value = $value;
        }
    }
    ```
- Make your classes **readonly** when possible. If not make properties **readonly** if they aren't meant to be changed after initialization.
  - This also allow to make the properties **public** and avoid the necessity of creating public getters.
- Evaluate if duplicated code can be refactored.
    - For example, a set of similar `if` conditions maybe can be refactored into a `match` expression.
- Use fluent setters **when a class is mutable** (e.g. builders, collections). An immutable value object should instead be `readonly` with public properties and no setters. Pick one model per class, don't mix the two.
- Include the final keyword in all the classes that will not be extended, **including** tests.
  - If your class is meant to be extended you should consider making them **abstract**.
  - Do not make a class not final just for unit-testing reasons. Define an **interface** instead.

# Tests

- You should use the **static assertion** methods of PHPUnit and the **non-static** methods for the expectations:
  ###### DO
  ```php
  self::assertEquals(...);

  $mock
    ->expects($this->once())
    ->method('method');
  }
  ```
  ###### DON´T
  ```php
  $this->assertEquals(...);

  $mock
    ->expects(self::once())
    ->method('method');
  ```
    - This does not apply if you are using or extending test cases that have non-static assertion methods.
      - For those case you should still use the `$this->assertSomethingFromCustomTestCase()` call.
      - Matcher methods like `once()`/`never()`/`any()`/`exactly()` are **not** static assertions, so call them as `$this->once()`, not `self::once()` (as shown in the DO/DON'T above).
- Default to `assertEquals`. Use `assertSame` only when you are asserting the **same instance** (identity), not merely equal value.
- For tests with multiple cases you should use PHPUnit `DataProvider` attribute (not the old annotation)
- The data provider method should come **after** the test (unless it is a data provider used for multiple tests)
  - The data provider must be a static method
    - If you need to create mock objects on the data provider, then consider using `callable`/`Closure` as the type of your argument. Then, in your test method call it, passing the current instance (e.g. the `TestCase` instance being executed).
    ###### EXAMPLE
    ```php

    public function testMyMethod(callable $myArgument): void
    {
        // Call the callable to get the actual value
        $myArgumentValue = $myArgument($this);

        // Now you can use the mock object created by the callable
        $myArgumentValue
            ->expects($this->once())
            ->method('doStuff')
            ->willReturn([1, 2, 3]);
    }

    public static function myMethodDataProvider(): array
    {
        // We can not call `createMock` in a static context, so we pass a callable to the test method
        return [
            'first_case' => [
                // This function will be called by the test method and will pass the current instance of this test.
                // Inside the callable we can use the $testCase argument to call the `createMock` method
                fn(self $testCase): MockObject&MyArgument => $testCase->createMock(MyArgument::class),
            ],
        ];
    }
    ```

- The data provider method should be named according to the test method:
  - The format is `testMethod` => `methodDataProvider`
    - Example: if the test method is `testSomething` then the data provider method should be `somethingDataProvider`
  - If the data provider is meant to be shared across several test it still should be name as `sharedNameDataProvider`
  - If you only have one test, then you can name it just `dataProvider`
  - 💡 **TL;DR** - Convention for data provider methods is ***something*DataProvider**
  - Datasets names should be in snake case to facilitate copy and paste and running individual datasets (`phpunit --filter testName@dataset_name`)
  ```php
  #[DataProvider('successOnCreationProfileDataProvider')]
  public function testSuccessOnCreationProfile(array $data): void
  {
     // your test code
  }

  public static function successOnCreationProfileDataProvider(): array
  {
    return [
        // test cases data
        'my_first_test' => [
        ]
    ];
  }
  ```
- Class properties that are mocks should have `MockObject` as a **union type** as the first type of the property.
    - Logic is that you can read it as `MockObject of XXXX`

    ```php
    private MockObject&LoggerInterface $logger;
    ```

- If a mock object does not define any expectation, then it should not be `MockObject` but rather's PHPUnit `Stub`
  - And if defined as property then it should also be a **union type** of `Stub`
    - Logic is that you can read it as `Stub of XXXX`

---

[Back to Contributing](CONTRIBUTING.md)
