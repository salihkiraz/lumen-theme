<?php

class ThemeInfoTest extends TestCase
{

    public function testGetters()
    {
        $themeInfo = new Lukeed\Theme\ThemeInfo();
        $themeInfo->setName('test-theme');
        $themeInfo->setVersion('1.0');
        $themeInfo->setDirectory('test');
        $themeInfo->setPath('/test/path/to/themes');

        $this->assertEquals('test-theme', $themeInfo->getName());
        $this->assertEquals('1.0', $themeInfo->getVersion());
        $this->assertEquals('test', $themeInfo->getDirectory());
        $this->assertEquals('/test/path/to/themes', $themeInfo->getPath());
        $this->assertEquals(null, $themeInfo->getDescription());
        $this->assertEquals('Theme: test-theme', $themeInfo);
    }

}
