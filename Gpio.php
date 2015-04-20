<?php

namespace Phprpi;

class Gpio
{

    //Possible modes of the pins
    const MODE_IN = 'in';
    const MODE_OUT = 'out';
    const MODE_PWM = 'pwm';
    const MODE_CLOCK = 'clock';
    const MODE_UP = 'up';
    const MODE_DOWN = 'down';
    const MODE_TRI = 'tri';

    // Valid modes for GPIO pins
    private $modes = array(
        Gpio::MODE_IN,
        Gpio::MODE_OUT,
        Gpio::MODE_PWM,
        Gpio::MODE_CLOCK,
        Gpio::MODE_UP,
        Gpio::MODE_DOWN,
        Gpio::MODE_TRI,
    );

    //output values of the pins
    const IO_VALUE_ON = 1;
    const IO_VALUE_OFF = 0;
    //list of WiringPI commands
    const MODE_COMMAND = 'gpio {BCM} mode {PINNO} {MODE}';
    const READ_COMMAND = 'gpio {BCM} read {PINNO}';
    const WRITE_COMMAND = 'gpio {BCM} write {PINNO} {VALUE}';
    const PWM_COMMAND = 'gpio {BCM} pwm {PINNO} {VALUE}';
    const AWRITE_COMMAND = 'gpio {BCM} awrite {PINNO} {VALUE}';
    const AREAD_COMMAND = 'gpio {BCM} aread {PINNO}';
    const PWMMS_COMMAND = 'gpio pwm-ms';
    const PWMR_COMMAND = 'gpio pwmr {VALUE}';

    // Complete list of Raspberry PI v2 pins
    private $pins = array(
        1 => '3V3 Power',
        2 => '5V Power',
        3 => 'GPIO2 SDA1 I2C',
        4 => '5V Power',
        5 => 'GPIO3 SCL1 I2C',
        6 => 'Ground',
        7 => 'GPIO4',
        8 => 'GPIO14 UART0_TXD',
        9 => 'Ground',
        10 => 'GPIO15 UART0_RXD',
        11 => 'GPIO17',
        12 => 'GPIO18 PCM_CLK',
        13 => 'GPIO27',
        14 => 'Ground',
        15 => 'GPIO22',
        16 => 'GPIO23',
        17 => '3V3 Power',
        18 => 'GPIO24',
        19 => 'GPIO10 SPI0_MOSI',
        20 => 'Ground',
        21 => 'GPIO9 SPI0_MISO',
        22 => 'GPIO25',
        23 => 'GPIO10 SPI0_SCLK',
        24 => 'GPIO8 SPI0_CE0_N',
        25 => 'Ground',
        26 => 'GPIO7 SPI0_CE1_N',
        27 => 'ID_SD I2C ID EEPROM',
        28 => 'ID_SC I2C ID EEPROM',
        29 => 'GPIO5',
        30 => 'Ground',
        31 => 'GPIO6',
        32 => 'GPIO12',
        33 => 'GPIO13',
        34 => 'Ground',
        35 => 'GPIO19',
        36 => 'GPIO16',
        37 => 'GPIO26',
        38 => 'GPIO20',
        39 => 'Ground',
        40 => 'GPIO21',
    );
    //list of pins that are GPIO
    //the mapping is pinNO = > BCM pinNO
    private $gpioPins = array(
        3 => 2,
        5 => 3,
        7 => 4,
        8 => 14,
        10 => 15,
        11 => 17,
        12 => 18,
        13 => 27,
        15 => 22,
        16 => 23,
        18 => 24,
        19 => 10,
        21 => 9,
        22 => 25,
        23 => 10,
        24 => 8,
        26 => 7,
        29 => 5,
        31 => 6,
        32 => 12,
        33 => 13,
        35 => 19,
        36 => 16,
        37 => 26,
        38 => 20,
        40 => 21,
    );
    //Valid values for pin output
    private $outputs = array(
        Gpio::IO_VALUE_ON,
        Gpio::IO_VALUE_OFF,
    );
    // exported pins for when we unexport all
    private $exportedPins = array();
    // if true pin numbers are to be interpreted as BCM_GPIO pin numbers 
    private $bcm;

    public function __construct($bcm = true)
    {
        $this->bcm = $bcm;
    }

    /**
     * Check if the given pinNo is a valid GPIO pin
     * 
     * @param int $pinNO
     * 
     * @return boolean
     */
    public function isGpio($pinNo)
    {
        if ($this->bcm) {
            return in_array($pinNo, $this->gpioPins);
        }
        return key_exists($this->hackablePins, $this->gpioPins);
    }

    /**
     * Checks if the given mode is valid 
     * 
     * @param int $pinNO
     *
     * @return boolean
     */
    public function isMode($mode)
    {
        return in_array($mode, $this->modes);
    }

    /**
     * Adds the -g parameter to a command based on BCM true or not
     * 
     * @param string $command
     * 
     * @return string
     */
    public function formatCommand($command)
    {
        if ($this->bcm) {
            return str_replace('{BCM}', '-g', $command);
        } else {
            return str_replace('{BCM}', '', $command);
        }
    }

    /**
     * Sets pin mode, takes pin number and mode
     *
     * @param int $pinNO
     * @param string $mode
     *
     * @return error message or boolean
     */
    public function mode($pinNo, $mode)
    {
        if (!$this->isGpio($pinNo)) {
            return false;
        }

        // if valid mode then set it
        if ($this->isMode($mode)) {
            $command = str_replace(array('{PINNO}', '{MODE}'), array($pinNo, $mode), GPIO::MODE_COMMAND);
            exec($this->formatCommand($command), $return);
        }

        return (!isset($return[0]) || trim($return[0]) == '') ? true : trim($return[0]);
    }

    /**
     * Read pin value
     *
     * @param int $pinNo
     *
     * @return integer or boolean false
     */
    public function read($pinNo)
    {
        if (!$this->isGpio($pinNo)) {
            return false;
        }
        $command = str_replace(array('{PINNO}'), array($pinNo), Gpio::READ_COMMAND);
        exec($this->formatCommand($command), $return);

        return (!isset($return[0]) || trim($return[0]) == '') ? true : trim($return[0]);
    }

    /**
     * Write pin value
     *
     * @param int $pinNo
     * @param int $value
     *
     * @return error message or boolean
     */
    public function write($pinNo, $value)
    {
        if (!$this->isGpio($pinNo) || (!in_array($value, $this->outputs))) {
            return false;
        }

        $command = str_replace(array('{PINNO}', '{VALUE}'), array($pinNo, $value), Gpio::WRITE_COMMAND);
        exec($this->formatCommand($command), $return);

        return (!isset($return[0]) || trim($return[0]) == '') ? true : trim($return[0]);
    }

    /**
     * Inverse the pin value
     * 
     * @param int $pinNo
     * 
     * @return pin new value or boolean false
     */
    public function inverse($pinNo)
    {
        $value = $this->read($pinNo);
        $newValue = abs(1 - (int) $value);

        if ($this->write($pinNo, $newValue)) {
            return $newValue;
        }

        return false;
    }

    /**
     * Sets the PWM mode to Mark-Space ratio
     */
    public function pwmMs()
    {
        exec(Gpio::PWMMS_COMMAND, $return);

        return (!isset($return[0]) || trim($return[0]) == '') ? true : trim($return[0]);
    }

    /**
     * Sets the PWM range register
     */
    public function pwmMr($value)
    {
        exec(str_replace('{VALUE}', $value, Gpio::PWMR_COMMAND), $return);

        return (!isset($return[0]) || trim($return[0]) == '') ? true : trim($return[0]);
    }
    
    /**
     * Write PWM pin value
     *
     * @param int $pinNo
     * @param int $value
     *
     * @return error message or boolean
     */
    public function pwm($pinNo, $value)
    {
        if (!$this->isGpio($pinNo)) {
            return false;
        }

        $command = str_replace(array('{PINNO}', '{VALUE}'), array($pinNo, $value), Gpio::PWM_COMMAND);
        exec($this->formatCommand($command), $return);

        return (!isset($return[0]) || trim($return[0]) == '') ? true : trim($return[0]);
    }
}
