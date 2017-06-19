<?php
namespace PhpGpio;
require_once("GpioInterface.php");

class Gpio implements GpioInterface
{
    // Using BCM pin numbers.
    private $pins;
    private $hackablePins;

    /**
     * @link http://www.raspberrypi-spy.co.uk/2012/06/simple-guide-to-the-rpi-gpio-header-and-pins/
     */
    public function __construct()
    {
        $this->pins = array(
        	0, 1, 6, 7, 8, 9, 45, 46, 15, 16, 17, 2, 3, 11, 5, 4, 19, 18, 38, 12, 13
        );
    }

    /**
     * getHackablePins : the pins you can hack with.
     * @link http://elinux.org/RPi_Low-level_peripherals
     * @return integer[]
     */
    public function getHackablePins()
    {
        return $this->hackablePins;
    }

    private $directions = array(
        GpioInterface::DIRECTION_IN,
        GpioInterface::DIRECTION_OUT,
    );

    private $outputs = array(
        GpioInterface::IO_VALUE_ON,
        GpioInterface::IO_VALUE_OFF,
    );

    // exported pins for when we unexport all
    private $exportedPins = array();

    /**
     * Setup pin, takes pin number and direction (in or out)
     *
     * @param  int    $pinNo
     * @param  string $direction
     * @return mixed  string GPIO value or boolean false
     */
    public function setup($pinNo, $direction)
    {
        if (!$this->isValidPin($pinNo)) {
            return false;
        }

        // if exported, unexport it first
        if ($this->isExported($pinNo)) {
            $this->unexport($pinNo);
        }

        // Export pin
        file_put_contents(GpioInterface::PATH_EXPORT, $pinNo);

        // if valid direction then set direction
        if ($this->isValidDirection($direction)) {
            file_put_contents(GpioInterface::PATH_GPIO.$pinNo.'/direction', $direction);
        }

        // Add to exported pins array
        $this->exportedPins[] = $pinNo;

        return $this;
    }

    /**
     * Get input value
     *
     * @param  int   $pinNo
     * @return false|string string GPIO value or boolean false
     */
    public function input($pinNo)
    {
        if (!$this->isValidPin($pinNo)) {
            return false;
        }
        if ($this->isExported($pinNo)) {
            if ($this->currentDirection($pinNo) != "out") {
                return trim(file_get_contents(GpioInterface::PATH_GPIO.$pinNo.'/value'));
            }
            throw new \Exception('Error!' . $this->currentDirection($pinNo) . ' is a wrong direction for this pin!');
        }

        return false;
    }

    /**
     * Set output value
     *
     * @param  int    $pinNo
     * @param  string $value
     * @return mixed  Gpio current instance or boolean false
     */
    public function output($pinNo, $value)
    {
        if (!$this->isValidPin($pinNo)) {
            return false;
        }
        if (!$this->isValidOutput($value)) {
            return false;
        }
        if ($this->isExported($pinNo)) {
            if ($this->currentDirection($pinNo) != "in") {
                file_put_contents(GpioInterface::PATH_GPIO.$pinNo.'/value', $value);
            } else {
                throw new \Exception('Error! Wrong Direction for this pin! Meant to be out while it is ' . $this->currentDirection($pinNo));
            }
        }

        return $this;
    }

    /**
     * Unexport Pin
     *
     * @param  int   $pinNo
     * @return mixed Gpio current instance or boolean false
     */
    public function unexport($pinNo)
    {
        if (!$this->isValidPin($pinNo)) {
            return false;
        }
        if ($this->isExported($pinNo)) {
            file_put_contents(GpioInterface::PATH_UNEXPORT, $pinNo);
            foreach ($this->exportedPins as $key => $value) {
                if($value == $pinNo) unset($key);
            }
        }

        return $this;
    }

    /**
     * Unexport all pins
     *
     * @return Gpio Gpio current instance or boolean false
     */
    public function unexportAll()
    {
        foreach ($this->exportedPins as $pinNo) {
            file_put_contents(GpioInterface::PATH_UNEXPORT, $pinNo);
        }
        $this->exportedPins = array();

        return $this;
    }

    /**
     * Check if pin is exported
     *
     * @return boolean
     */
    public function isExported($pinNo)
    {
        if (!$this->isValidPin($pinNo)) {
            return false;
        }

        return file_exists(GpioInterface::PATH_GPIO.$pinNo);
    }

    /**
     * get the pin's current direction
     *
     * @return false|string string pin's direction value or boolean false
     */
    public function currentDirection($pinNo)
    {
        if (!$this->isValidPin($pinNo)) {
            return false;
        }

        return trim(file_get_contents(GpioInterface::PATH_GPIO.$pinNo.'/direction'));
    }

    /**
     * Check for valid direction, in or out
     *
     * @exception InvalidArgumentException
     * @return boolean true
     */
    public function isValidDirection($direction)
    {
        if (!is_string($direction) || empty($direction)) {
            throw new \InvalidArgumentException(sprintf('Direction "%s" is invalid (string expected).', $direction));
        }
        if (!in_array($direction, $this->directions)) {
            throw new \InvalidArgumentException(sprintf('Direction "%s" is invalid (unknown direction).', $direction));
        }

        return true;
    }

    /**
     * Check for valid output value
     *
     * @exception InvalidArgumentException
     * @return boolean true
     */
    public function isValidOutput($output)
    {
        if (!is_int($output)) {
            throw new \InvalidArgumentException(sprintf('Pin value "%s" is invalid (integer expected).', $output));
        }
        if (!in_array($output, $this->outputs)) {
            throw new \InvalidArgumentException(sprintf('Output value "%s" is invalid (out of exepected range).', $output));
        }

        return true;
    }

    /**
     * Check for valid pin value
     *
     * @exception InvalidArgumentException
     * @return boolean true
     */
    public function isValidPin($pinNo)
    {
        if (!is_int($pinNo)) {
            throw new \InvalidArgumentException(sprintf('Pin number "%s" is invalid (integer expected).', $pinNo));
        }
        if (!in_array($pinNo, $this->pins)) {
            throw new \InvalidArgumentException(sprintf('Pin number "%s" is invalid (out of exepected range).', $pinNo));
        }

        return true;
    }
}
