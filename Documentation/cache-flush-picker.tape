Output Documentation/Images/cache-flush-picker.gif

Set Theme { "background": "#0d1117", "foreground": "#c9d1d9", "black": "#0d1117", "red": "#ff7b72", "green": "#7ee787", "yellow": "#e3b341", "blue": "#79c0ff", "magenta": "#d2a8ff", "cyan": "#a5d6ff", "white": "#f0f6fc", "brightBlack": "#6e7681", "brightRed": "#ffa198", "brightGreen": "#56d364", "brightYellow": "#e3b341", "brightBlue": "#79c0ff", "brightMagenta": "#d2a8ff", "brightCyan": "#a5d6ff", "brightWhite": "#f0f6fc" }
Set FontSize 16
Set Width 1400
Set Height 820
Set Padding 18
Set TypingSpeed 55ms
Set Framerate 30
Set Shell "bash"

Hide
Type `export PS1='$ ' && export TERM=xterm-256color && export COLORTERM=truecolor && clear`
Enter
Sleep 400ms
Show

Type `ddev exec './vendor/bin/typo3 cache:flush --cache=fluid'`
Sleep 600ms
Enter
Sleep 5s
Type `fluid_template`
Sleep 1s
Enter
Sleep 3s
