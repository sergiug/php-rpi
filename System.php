<?php

namespace Phprpi;

/**
 * Class containing useful for retrieving system values (model, version, temp, etc...)
 * 
 */
class System
{
    //Array with Revision code -> Pi Model associations
    private $models = array(
        '0002' => 'Model B Revision 1.0',
        '0003' => 'Model B Revision 1.0 + ECN0001',
        '0004' => 'Model B Revision 2.0',
        '0005' => 'Model B Revision 2.0',
        '0006' => 'Model B Revision 2.0',
        '0007' => 'Model A',
        '0008' => 'Model A',
        '0009' => 'Model A',
        '000d' => 'Model B Revision 2.0',
        '000e' => 'Model B Revision 2.0',
        '000f' => 'Model B Revision 2.0',
        '0010' => 'Model B+',
        '0011' => 'Compute Module',
        '0012' => 'Model A+',
        'a01041' => 'RaspberryPi 2 Model B (UK)',
        'a21041' => 'RaspberryPi 2 Model B (China)',
    );

    /**
     * Returns the Raspberry Pi model based on $models association
     * 
     * @return string
     */
    public function model()
    {
        if (preg_match('/Revision\s*:\s*([^\s]*)\s*/', file_get_contents('/proc/cpuinfo'), $matches)) {
            if (isset($this->models[$matches[1]])) {
                return $this->models[$matches[1]];
            }
        }
        return 'Unknown model';
    }

    /**
     * Returns the Raspberry Pi OS Version
     * 
     * @return string
     */
    public function version()
    {
        exec('uname -a', $result);
        if (preg_match('/(.*)#/', $result[0], $matches)) {
            return trim($matches[1]);
        }
        return 'Unknown version';
    }

    /**
     * Returns the frequency of the CPU
     * 
     * @return float
     */
    public function cpuFreq()
    {
        return floatval(file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq')) / 1000;
    }

    /**
     * Returns the load averages of the CPU
     * 
     * @return string
     */
    public function cpuLoad()
    {
        return implode(', ',sys_getloadavg());
    }

    /**
     * Returns the temperature of the CPU
     * 
     * @return float
     */
    public function cpuTemp()
    {
        return number_format(floatval(file_get_contents('/sys/class/thermal/thermal_zone0/temp')) / 1000, 2);
    }

    /**
     * Returns the temperature of the GPU
     * 
     * @return float
     */
    public function gpuTemp()
    {
        exec('/opt/vc/bin/vcgencmd measure_temp', $return);
        $temp = preg_replace('/temp=(.*)\'C/', '${1}', $return[0]);
        return number_format(floatval($temp), 2);
    }
}
