#![allow(
    unused
)]

use std::path::PathBuf;
use std::{fs, thread};
use std::io::{self, BufReader, BufRead, Write};
use std::collections::HashMap;

use crossterm::{
    event::{self, Event, KeyEvent, KeyEventKind, KeyCode, KeyModifiers},
    execute,
    queue,
    terminal::{self, EnterAlternateScreen, LeaveAlternateScreen},
    style::{self, Color, Print, SetForegroundColor},
    cursor::{self},
};

fn main() -> io::Result<()> {

    // 进入备用屏幕模式
    let mut stdout = io::stderr();
    execute!(stdout, EnterAlternateScreen)?;
    terminal::enable_raw_mode().unwrap();

    // 获取当前目录路径
    let mut current_dir = fs::canonicalize(".")?; // 使用 canonicalize 来获取当前目录的绝对路径
    let mut path_index_map: HashMap<String, usize> = HashMap::new();
    let mut curr_mode = String::from("");
    let mut search_query = String::from("");

    // 创建一个 Vec 来存储文件历史记录
    let mut entries: Vec<(String, usize)> = Vec::new();

    // 读取~/.bash_history并遍历所有行
    let bash_history_path = PathBuf::from(std::env::var("HOME").unwrap() + "/.bash_history");
    let file = fs::File::open(bash_history_path.clone())?;
    let reader = io::BufReader::new(file);
    let mut line_count = 0;
    for line in reader.lines() {
        let line = line?;
        // 判断line是否是#号开头,如果是就跳过
        if line.starts_with("#") {
            continue;
        }
        line_count += 1;
        entries.push((line, line_count));
    }

    // 反转entries
    entries.reverse();
    
    let mut highlighted_line = 0;
    let mut selected_line = 0;
    let mut pre_chr = String::from("");

    // 清屏并打印所有行
    update_highlighted_line(&mut stdout, &current_dir, &entries, highlighted_line)?;
    execute!(stdout, cursor::Hide, cursor::MoveTo(0, 0))?;
    
    loop {
        match event::read()? {
            Event::Key(KeyEvent {
                code: KeyCode::Up,
                kind: KeyEventKind::Press,
                modifiers: _,
                state: _,
            }) => {
                // 让selected_line为一个不存在的索引
                selected_line = 99999999;
                // 如果已经在第一行，不做任何操作
                if highlighted_line > 0 {
                    highlighted_line -= 1;
                    // 更新高亮行
                    update_highlighted_line(&mut stdout, &current_dir, &entries, highlighted_line)?;
                }
            },
            Event::Key(KeyEvent {
                code: KeyCode::Down,
                kind: KeyEventKind::Press,
                modifiers: _,
                state: _,
            }) => {
                // 让selected_line为一个不存在的索引
                selected_line = 99999999;
                // 如果已经在最后一行，不做任何操作
                if highlighted_line < entries.len() - 1 {
                    highlighted_line += 1;
                    // 更新高亮行
                    update_highlighted_line(&mut stdout, &current_dir, &entries, highlighted_line)?;
                }
            },
            Event::Key(KeyEvent {
                code: KeyCode::Enter,
                kind: KeyEventKind::Press,
                modifiers: _,
                state: _,
            }) => {
                if curr_mode == String::from("search") {
                    // search_query 按空格分组
                    let search_query_vec = search_query.split(" ").collect::<Vec<&str>>();
                    for (index, line) in entries.clone().iter().enumerate() {
                        // 判断line是否同时包含所有search_query_vec
                        let mut is_match = true;
                        for query in search_query_vec.clone().iter() {
                            if !line.0.contains(query) {
                                is_match = false;
                                break;
                            }
                        }
                        if is_match && index > highlighted_line {
                            // 如果index大于highlighted_line,则更新highlighted_line
                            highlighted_line = index;
                            update_highlighted_line(&mut stdout, &current_dir, &entries, highlighted_line)?;
                            break;
                        }
                        // 如果是最后一行则从头匹配
                        if index == entries.len() - 1 {
                            highlighted_line = 0;
                            break;
                        }
                    }
                }
            },
            Event::Key(KeyEvent {
                code: KeyCode::Char('/'),
                kind: KeyEventKind::Press,
                modifiers: _,
                state: _,
            }) => {
                curr_mode = String::from("search");
                // 更新显示
                update_search_input(&mut stdout, &search_query)?;
            },
            Event::Key(KeyEvent {
                code: KeyCode::Backspace,
                kind: KeyEventKind::Press | KeyEventKind::Repeat,
                modifiers: KeyModifiers::NONE,
                state: _,
            }) => {
                if curr_mode == String::from("search") {
                    // 删除搜索关键词的最后一个字符
                    if !search_query.is_empty() {
                        search_query.pop();
                    }
                    // 更新显示
                    update_search_input(&mut stdout, &search_query)?;
                }
            },
            Event::Key(KeyEvent {
                code: KeyCode::Char(' '),
                kind: KeyEventKind::Press,
                modifiers: KeyModifiers::NONE,
                state: _,
            }) => {
                if curr_mode == String::from("search") {
                    // 在搜索模式下，将空格添加到搜索关键词
                    search_query.push(' ');
                    update_search_input(&mut stdout, &search_query)?;
                }
            },
            Event::Key(KeyEvent {
                code: KeyCode::Char(chr),
                kind: KeyEventKind::Press,
                modifiers: (KeyModifiers::NONE | KeyModifiers::SHIFT),
                state: _,
            }) if chr.is_alphanumeric() => { // 支持字母和数字键
                // execute!(stdout, cursor::MoveTo(0, 20))?;
                // execute!(stdout, Print(chr)).unwrap();
                if curr_mode == String::from("search") {
                    // 在搜索模式下，将字符添加到搜索关键词
                    search_query.push(chr);
                    // 移动到最后一行并显示搜索关键词
                    update_search_input(&mut stdout, &search_query)?;
                } else {
                    // 让selected_line为一个不存在的索引
                    selected_line = 99999999;
                    for (index, line) in entries.iter().enumerate() {
                        if !line.0.is_empty() && line.0[0..1] == String::from(chr) {
                            if pre_chr == String::from(chr) {
                                if index > highlighted_line {
                                    highlighted_line = index;
                                    break;
                                }
                            } else {
                                pre_chr = String::from(chr);
                                highlighted_line = index;
                                break;
                            }
                        }
                    }
                    update_highlighted_line(&mut stdout, &current_dir, &entries, highlighted_line)?;
                }
            },
            /* Event::Key(KeyEvent {
                code: KeyCode::Char('c'),
                kind: KeyEventKind::Press,
                modifiers: KeyModifiers::CONTROL,
                state: _,
            }) => {
                execute!(stdout, cursor::MoveTo(0, 20))?;
                execute!(stdout, Print("Crtl+C")).unwrap();
            }, */
            Event::Key(KeyEvent {
                code: KeyCode::Esc,
                kind: KeyEventKind::Press,
                modifiers: _,
                state: _,
            }) => {
                if curr_mode == String::from("search") {
                    curr_mode = String::from("");
                    selected_line = highlighted_line;
                    highlighted_line = 0;
                    update_highlighted_line(&mut stdout, &current_dir, &entries, 99999999)?;
                } else {
                    execute!(stdout, cursor::SetCursorStyle::DefaultUserShape).unwrap();
                    break;
                }
            },
            _ => {}
        }
    }

    // 打印文本
    // thread::sleep(std::time::Duration::from_secs(10));

    // entries 匹配 highlighted_line
    let mut selected_line_str = String::from("");
    for (index, line) in entries.iter().enumerate() {
        if selected_line < 99999999 {
            if index == selected_line {
                selected_line_str = line.0.clone();
                break;
            }
        } else {
            if index == highlighted_line {
                selected_line_str = line.0.clone();
                break;
            }
        }
    }

    // 离开备用屏幕模式
    execute!(stdout, terminal::Clear(terminal::ClearType::All))?;
    execute!(stdout, style::ResetColor, cursor::Show, cursor::MoveTo(0, 0), LeaveAlternateScreen)?;
    println!("{}", selected_line_str); // 输出当前选择
    execute!(stdout, cursor::MoveToColumn(0))?;
    terminal::disable_raw_mode()

    // Ok(())
}

fn update_search_input(stdout: &mut impl Write, search_input: &String) -> io::Result<()> {
    // 移动到最后一行并显示搜索关键词
    let (w, h) = terminal::size()?;
    execute!(stdout, cursor::MoveTo(0, h))?;
    execute!(stdout, Print(" ".repeat(w as usize)))?; // 清除该行
    execute!(stdout, cursor::MoveTo(0, h))?;
    execute!(stdout, Print(format!("/{}", search_input)))?;
    stdout.flush()
}

fn update_highlighted_line(stdout: &mut impl Write, curr_path: &PathBuf, lines: &Vec<(String, usize)>, highlighted_line: usize) -> io::Result<()> {
    // 清除当前屏幕内容
    queue!(stdout, terminal::Clear(terminal::ClearType::All))?;

    queue!(stdout, cursor::MoveTo(0, 0))?;
    queue!(stdout, SetForegroundColor(Color::White), Print(curr_path.display().to_string()), SetForegroundColor(Color::Reset))?;
    // queue!(stdout, cursor::MoveToNextLine(2))?;
    queue!(stdout, cursor::MoveTo(0, 1))?;
    queue!(stdout, SetForegroundColor(Color::White), Print(format!("{}{}", "Total:", lines.len())), SetForegroundColor(Color::Reset))?;
    
    // 重新打印所有行，高亮当前行
    let mut nfoo: u16 = 2;
    let (w, h) = terminal::size()?;
    let canh = (h as usize) - 2;
    let mut page = highlighted_line.div_ceil(canh);

    if highlighted_line == 99999999 {
        page = 0;
    }
    
    if page == 0 {
        page = 1;
    }

    // execute!(stdout, cursor::MoveTo(0, 20))?;
    // execute!(stdout, Print(h))?;
    // execute!(stdout, cursor::MoveTo(0, 21))?;
    // execute!(stdout, Print(page))?;

    for (index, line) in lines.iter().enumerate() {
        if index < (page-1) * canh {
            continue;
        }
        if index >= page * canh {
            break;
        }
        // queue!(stdout, cursor::MoveToNextLine(1))?;
        queue!(stdout, cursor::MoveTo(0, nfoo))?;
        if index == highlighted_line {
            queue!( stdout, SetForegroundColor(Color::Magenta), Print(format!("{} {}", line.1, line.0)) )?;
        } else {
            // 打印普通行
            // execute!(stdout, Print(line.0.clone()))?;
            queue!( stdout, SetForegroundColor(Color::Cyan), Print(format!("{} {}", line.1, line.0)) )?;
        }
        queue!(stdout, SetForegroundColor(Color::Reset));
        // execute!(stdout, cursor::MoveToNextLine(1))?;
        nfoo = nfoo + 1;
    }

    stdout.flush()
}