<?php
/**
 * Target.php file.
 *
 * @author Dirk Adler <adler@spacedealer.de>
 * @link http://www.spacedealer.de
 * @copyright Copyright &copy; 2014 spacedealer GmbH
 */

namespace spacedealer\tests\loggly;

use yii\log\Logger;

/**
 * Class Target
 */
class Target extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Loggly Customer token. Please provide if you want to run live push tests.
     */
    protected $token = '';

    public function testGetUrl()
    {
        $target = new \spacedealer\loggly\Target([
            'baseUrl' => 'http://example.com',
            'customerToken' => '123456789012345678901234567890123456',
            'tags' => [
                'one',
                'two',
                'three',
            ],
        ]);

        $url = $target->getUrl();

        $this->assertEquals(
            'http://example.com/inputs/123456789012345678901234567890123456/tag/one,two,three/',
            $url
        );
    }

    public function testGetBatchUrl()
    {
        $target = new \spacedealer\loggly\Target([
            'baseUrl' => 'http://example.com',
            'customerToken' => '123456789012345678901234567890123456',
            'tags' => [
                'one',
                'two',
                'three',
            ],
            'bulk' => true,
        ]);

        $url = $target->getUrl();

        $this->assertEquals(
            'http://example.com/bulk/123456789012345678901234567890123456/tag/one,two,three/',
            $url
        );
    }

    public function testFormatMessage()
    {
        $target = new \spacedealer\loggly\Target([
            'baseUrl' => 'http://example.com/',
            'customerToken' => '123456789012345678901234567890123456',
            'tags' => [
                'one',
                'two',
                'three',
            ],
        ]);

        $message = [
            'log message',
            Logger::LEVEL_TRACE,
            'test',
            strtotime('20141202100110'),
        ];

        $formattedMessage = $target->formatMessage($message);
        $this->assertEquals(
            [
                'timestamp' => '2014/12/02 10:01:10',
                'level' => 'trace',
                'category' => 'test',
                'message' => 'log message',
            ],
            $formattedMessage
        );

        $target->enableIp = true;
        $formattedMessage = $target->formatMessage($message);
        $this->assertEquals(
            [
                'timestamp' => '2014/12/02 10:01:10',
                'level' => 'trace',
                'category' => 'test',
                'message' => 'log message',
                'ip' => '0.0.0.0',
            ],
            $formattedMessage
        );

        $target->enableTrail = true;
        $target->trail = '61b5e46fa4f60638ef7d785bbb67023a';
        $formattedMessage = $target->formatMessage($message);
        $this->assertEquals(
            [
                'timestamp' => '2014/12/02 10:01:10',
                'level' => 'trace',
                'category' => 'test',
                'message' => 'log message',
                'ip' => '0.0.0.0',
                'trail' => '61b5e46fa4f60638ef7d785bbb67023a',
            ],
            $formattedMessage
        );
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testEmptyCustomerToken()
    {
        new \spacedealer\loggly\Target();
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testInvalidCustomerToken()
    {
        new \spacedealer\loggly\Target([
            'customerToken' => 'notvalid',
        ]);
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testNotStringCustomerToken()
    {
        new \spacedealer\loggly\Target([
            'customerToken' => false,
        ]);
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testInvalidCert()
    {
        new \spacedealer\loggly\Target([
            'cert' => 'wrong.file',
        ]);
    }

    /**
     * Test default message push.
     * This test will be skipped if no loggly customer token is provided.
     */
    public function testLiveExport()
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Loggly Customer token not provided.');
        }

        $target = new \spacedealer\loggly\Target([
            'customerToken' => $this->token,
            'tags' => [
                'test',
                'bulk',
            ],
            'bulk' => false,
        ]);

        $target->messages = [
            [
                'first log message',
                Logger::LEVEL_TRACE,
                'test',
                strtotime('20141202100110'),
            ],
            [
                'second log message',
                Logger::LEVEL_TRACE,
                'test',
                strtotime('20141202100110'),
            ],
        ];
        $target->export();
    }

    /**
     * Test bulk message push.
     * This test will be skipped if no loggly customer token is provided.
     */
    public function testLiveBulkExport()
    {
        if (empty($this->token)) {
            $this->markTestSkipped('Loggly Customer token not provided.');
        }

        $target = new \spacedealer\loggly\Target([
            'customerToken' => $this->token,
            'tags' => [
                'test',
                'bulk',
            ],
            'bulk' => true,
        ]);

        $target->messages = [
            [
                'first log message',
                Logger::LEVEL_TRACE,
                'test',
                strtotime('20141202100110'),
            ],
            [
                'second log message',
                Logger::LEVEL_TRACE,
                'test',
                strtotime('20141202100110'),
            ],
        ];
        $target->export();
    }
}
