<?php
declare(strict_types=1);

// Yangon venues for the meetup styles already used by the app.
// Grouped by township. Addresses come from public listings (maps, cinema
// directories, restaurant guides, hotel pages, and the Hlawga Park site).
// A shop can move or close; this is a starting catalog, not a live map.

$townships = [
    'Thingangyun' => ['mm' => 'သင်္ဃန်းကျွန်း', 'sort' => 10],
    'Tamwe' => ['mm' => 'တာမွေ', 'sort' => 20],
    'Bahan' => ['mm' => 'ဗဟန်း', 'sort' => 30],
    'Yankin' => ['mm' => 'ရန်ကင်း', 'sort' => 40],
    'Kamayut' => ['mm' => 'ကမာရွတ်', 'sort' => 50],
    'Sanchaung' => ['mm' => 'စမ်းချောင်း', 'sort' => 60],
    'Mayangone' => ['mm' => 'မရမ်းကုန်း', 'sort' => 70],
    'Ahlone' => ['mm' => 'အလုံ', 'sort' => 80],
    'Lanmadaw' => ['mm' => 'လမ်းမတော်', 'sort' => 90],
    'Latha' => ['mm' => 'လသာ', 'sort' => 100],
    'Pabedan' => ['mm' => 'ပန်းဘဲတန်း', 'sort' => 110],
    'Kyauktada' => ['mm' => 'ကျောက်တံတား', 'sort' => 120],
    'Botataung' => ['mm' => 'ဗိုလ်တထောင်', 'sort' => 130],
    'Dagon' => ['mm' => 'ဒဂုံ', 'sort' => 140],
    'Mingalar Taung Nyunt' => ['mm' => 'မင်္ဂလာတောင်ညွန့်', 'sort' => 150],
    'South Okkalapa' => ['mm' => 'တောင်ဥက္ကလာပ', 'sort' => 160],
    'Mingaladon' => ['mm' => 'မင်္ဂလာဒုံ', 'sort' => 170],
];

$styles = [
    'karaoke',
    'zoo',
    'staycation',
    'camp',
    'bar',
    'coffee',
    'restaurant',
    'fine_dining',
    'tea_house',
    'movie',
    'show',
];

// style, township, name, address, detail
$raw = [
    // Coffee
    ['coffee', 'Thingangyun', 'YOON Coffee', 'ရတနာလမ်း', 'ရတနာလမ်းမပေါ်က ကော်ဖီဆိုင်။'],
    ['coffee', 'Thingangyun', "3 O'Clock Cafe", 'ရတနာလမ်း၊ အောင်ရတနာဆေးခန်းရှေ့', 'ကော်ဖီ၊ bubble tea နဲ့ အသားကင် မှာနိုင်တဲ့ ဆိုင်။'],
    ['coffee', 'Bahan', 'Yangon Bakehouse (Pearl Condo)', 'ကမ္ဘာအေးဘုရားလမ်း၊ Pearl Condo Block C အောက်ထပ်', 'Pearl Condo အောက်ထပ်က လူမှုဝန်ထမ်းသင်တန်း ကော်ဖီဆိုင်။'],
    ['coffee', 'Bahan', 'Yangon Bakehouse (Dhammazedi)', 'ဓမ္မစေတီလမ်း အမှတ် ၁၅၀၊ Link Lane ထောင့်', 'ရွှေဂုံတိုင် Link Lane နဲ့ ဓမ္မစေတီလမ်းထောင့်က ဆိုင်။'],
    ['coffee', 'Bahan', 'Hta Naung Coffee Zayat', 'အင်းယားမြိုင်လမ်း အမှတ် ၄၇', 'ရွှေချိုင့်ထဲက ကော်ဖီဇရပ်။'],
    ['coffee', 'Kamayut', 'Coffee Circles', 'ဓမ္မစေတီလမ်း အမှတ် ၁၀၇ အေ', 'ဓမ္မစေတီလမ်းပေါ်က ကော်ဖီဆိုင်။'],
    ['coffee', 'Kamayut', 'Fuji Coffee House', 'တက္ကသိုလ်ရိပ်သာလမ်း အမှတ် ၁၁၆', 'တက္ကသိုလ်ရိပ်သာလမ်းပေါ်က ကော်ဖီဆိုင်။'],
    ['coffee', 'Sanchaung', 'Café Salween', 'ဘဂရာလမ်း အမှတ် ၂၇၁၊ Parkside One အထပ် ၈', 'မြေနီကုန်း၊ Dagon Center အနီး၊ MyHotel ဘေးက အပေါ်ဆုံးထပ်ဆိုင်။'],
    ['coffee', 'Sanchaung', 'Coffee Date', 'ပြည်လမ်း၊ Dagon Center အထပ် ၂', 'Dagon Center ထဲက ကော်ဖီဆိုင်။'],
    ['coffee', 'Sanchaung', 'Thae Lay Coffee', 'ဗဟိုလမ်း အမှတ် ၃၃၊ Home Mart အောက်ထပ်', 'ဗဟိုလမ်း Home Mart ထဲက ကော်ဖီဆိုင်။'],
    ['coffee', 'Sanchaung', 'JAK Rangoon Cafe', 'ဘဂရာလမ်း', 'စမ်းချောင်း ဘဂရာလမ်းပေါ်က ကော်ဖီဆိုင်။'],
    ['coffee', 'Ahlone', 'Eugenia Cafe', 'ဗဟိုလမ်း၊ မနော်ဟာရီရိပ်သာ E-101/102', 'အလုံ ဗဟိုလမ်းရိပ်သာထဲက ကဖေး။'],
    ['coffee', 'Pabedan', 'Yangon Bakehouse (Junction City)', 'ဗိုလ်ချုပ်အောင်ဆန်းလမ်း၊ Junction City အထပ် ၅', 'မြို့လယ် Junction City ထဲက Bakehouse ဆိုင်။'],
    ['coffee', 'Pabedan', 'Bar Boon', 'ဗိုလ်ချုပ်အောင်ဆန်းလမ်း အမှတ် ၃၈၀၊ FMI Centre အပြင်ဘက်', 'မြို့လယ် FMI Centre ရှေ့က ကော်ဖီဆိုင်။'],
    ['coffee', 'Kyauktada', 'Flavour Specialty Coffee', 'အမှတ် ၃၈ လမ်း', 'မြို့လယ် ၃၈ လမ်းက specialty ကော်ဖီဆိုင်။'],

    // Tea houses
    ['tea_house', 'Thingangyun', 'Shwe Bon Nan Tea House', 'ရတနာလမ်း အမှတ် ၁၅', 'ရတနာလမ်းက လက်ဖက်ရည်ဆိုင်။'],
    ['tea_house', 'Thingangyun', 'Taunggyi Si Taw Gyi', 'ရတနာလမ်း', 'တောင်ကြီးစီတော်ကြီး။ လက်ဖက်ရည်၊ ခေါက်ဆွဲနဲ့ ထမင်းဟင်းဆိုင်။'],
    ['tea_house', 'Tamwe', 'King Lashio Tea House', '၁၅၆ လမ်းနဲ့ ကြိုက်ကဆန်လမ်းထောင့် အမှတ် ၃၃၃', 'တာမွေဈေးအနီးက ခုနှစ်လွှာပေါက်စီ လက်ဖက်ရည်ဆိုင်။'],
    ['tea_house', 'Bahan', 'Rangoon Tea House', 'အင်းယားမြိုင်လမ်း အမှတ် ၃၆၊ ရွှေချိုင့်', 'ရွှေချိုင့်က မြန်မာလက်ဖက်ရည်ဆိုင်။ ထမင်းဟင်းလည်း တည်ခင်းသည်။'],
    ['tea_house', 'Bahan', 'Acacia Tea Salon', 'ဆရာစံလမ်း အမှတ် ၅၂', 'ကိုလိုနီအိမ်ဟောင်းထဲက လက်ဖက်ရည်ဆိုင်။'],
    ['tea_house', 'Kamayut', 'Rangoon Tea House (Junction Square)', 'ပြည်လမ်းနဲ့ ကြည်ဦးတော်လမ်းကြား၊ Junction Square', 'Junction Square ထဲက လက်ဖက်ရည်ဆိုင်ခွဲ။'],
    ['tea_house', 'Pabedan', 'Thone Pan Hla', 'မဟာဗန္ဓုလလမ်း အမှတ် ၄၅၄', 'ဆူးလေဘုရား အနောက်ဘက်က ရိုးရာလက်ဖက်ရည်ဆိုင်။'],
    ['tea_house', 'Botataung', 'Lucky Seven Tea House', 'အမှတ် ၄၉ လမ်း', 'မြို့လယ် ၄၉ လမ်းက လက်ဖက်ရည်ဆိုင်။ မုန့်ဟင်းခါးလည်း ရှိသည်။'],

    // Restaurants
    ['restaurant', 'Thingangyun', 'Ginki Bar & Restaurant', 'ရတနာလမ်း အမှတ် ၂', 'ရတနာလမ်းက စားသောက်ဆိုင်။'],
    ['restaurant', 'Bahan', 'Aung Thukha', 'ရွှေဂုံတိုင်အနောက် ပထမလမ်း အမှတ် ၁၇ အေ', 'ရွှေဂုံတိုင်အနောက်က မြန်မာဟင်းကျော်ဆိုင်။'],
    ['restaurant', 'Kamayut', 'House of Memories', 'ဦးဝိဇရာလမ်း အမှတ် ၂၉၀', 'ကိုလိုနီအိမ်ဟောင်းထဲက မြန်မာစာဆိုင်။'],
    ['restaurant', 'Kamayut', 'Danuphyu Daw Saw Yi', 'အင်းစိန်လမ်း အမှတ် ၉၆၊ စမ်းရိပ်ငြိမ်', 'မုန့်ဟင်းခါးနဲ့ မြန်မာစာ နာမည်ကြီးဆိုင်။'],
    ['restaurant', 'Kamayut', 'Khaing Khaing Kyaw', 'ပြည်လမ်း အမှတ် ၁၇ အေ၊ ရပ်ကွက် ၂', 'ပြည်လမ်းပေါ်က မြန်မာစာဆိုင်။'],
    ['restaurant', 'Mayangone', 'Feel Witoria', 'တော်ဝင်လမ်း အမှတ် ၆၈၊ ၉ မိုင်', '၉ မိုင်က Feel မြန်မာစာဆိုင်ခွဲ။'],
    ['restaurant', 'Mayangone', 'Kaung Thant', 'ကျိုက်ဝိုင်းဘုရားလမ်း အမှတ် ၃၃', 'ကျိုက်ဝိုင်းဘုရားလမ်းက မြန်မာစာဆိုင်။'],
    ['restaurant', 'Mayangone', 'Golden Duck', 'ပြည်လမ်း ၈ မိုင်၊ မြိုင်ဟေဝန်ဥယျာဉ်', '၈ မိုင် ပြည်လမ်းပေါ်က စားသောက်ဆိုင်။'],
    ['restaurant', 'Yankin', 'Feel Yankin', 'ရန်ကင်းစင်တာ မြေအောက်ထပ်', 'ရန်ကင်းစင်တာထဲက Feel ဆိုင်။'],
    ['restaurant', 'Lanmadaw', 'Shan Yoe Yar', 'ဝါဒန်လမ်း အမှတ် ၁၆၉', 'ဝါဒန်လမ်းက ရှမ်းစာဆိုင်။'],
    ['restaurant', 'Lanmadaw', 'New Doreen', 'အထက်ဝါဒန်လမ်း အမှတ် ၁၆၄-၁၆၈', 'အထက်ဝါဒန်လမ်းက စားသောက်ဆိုင်။'],
    ['restaurant', 'Lanmadaw', 'Feel 3 Gold', 'ဒနသီဟစင်တာ ပထမထပ်၊ ဗိုလ်ချုပ်လမ်းနဲ့ ဝါဒန်လမ်းထောင့်', 'လမ်းမတော်က Feel ဆိုင်ခွဲ။'],
    ['restaurant', 'Latha', '19th Street BBQ', '၁၉ လမ်း၊ တရုတ်တန်း', 'ညဈေး အသားကင်ဆိုင်တန်း။ ဆိုင်တစ်ဆိုင်တည်း မဟုတ်ပါ။'],
    ['restaurant', 'Latha', '113 Restaurant', 'ဗိုလ်ရွဲလမ်း အမှတ် ၁၁၃၊ အောက်ထပ်', 'လသာ ဗိုလ်ရွဲလမ်းက ထမင်းဟင်းဆိုင်။'],
    ['restaurant', 'Pabedan', 'Hot Pot King', 'ရွှေဘုံသာလမ်း အမှတ် ၂၁၆/၂၁၈', 'မြို့လယ် ရွှေဘုံသာလမ်းက အိုးကြီးဆိုင်။'],
    ['restaurant', 'Kyauktada', '999 Shan Noodle Shop', '၃၄ လမ်း အမှတ် ၁၃၀ ဘီ', '၃၄ လမ်းက ရှမ်းခေါက်ဆွဲဆိုင်ငယ်။'],
    ['restaurant', 'Dagon', 'Feel Myanmar Food', 'ပြည်ထောင်စုရိပ်သာလမ်း အမှတ် ၁၂၄', 'ပြည်ထောင်စုရိပ်သာလမ်းက မြန်မာဟင်းမျိုးစုံဆိုင်။'],
    ['restaurant', 'Ahlone', 'Myanmar Aroma', 'အောက်ကြည့်မြင်တိုင်လမ်း အမှတ် ၄၁၅/၄၂၃၊ ဂလုံးရပ်ကွက်', 'အလုံက မြန်မာစာဆိုင်။'],
    ['restaurant', 'Ahlone', 'Pansweltaw', 'အလုံလမ်း အမှတ် ၂၂၈', 'အလုံလမ်းက စားသောက်ဆိုင်။'],
    ['restaurant', 'South Okkalapa', 'Little Feel', 'သုမင်္ဂလာလမ်း၊ ရပ်ကွက် ၆', 'တောင်ဥက္ကလာပက Feel ဆိုင်ငယ်။'],
    ['restaurant', 'South Okkalapa', 'Khine Khine Kyaw', 'ပါရမီလမ်း အမှတ် ၄၂ အေ၊ ဝေဇယန္တာလမ်းထောင့်', 'ဝေဇယန္တာလမ်းထောင့်က မြန်မာစာဆိုင်။'],
    ['restaurant', 'Mingalar Taung Nyunt', 'Karaweik Palace Restaurant', 'ကန်တော်ကြီး အရှေ့ဘက်ကမ်း', 'ကန်ဘေး ညစာ buffet ဆိုင်။'],

    // Fine dining
    ['fine_dining', 'Kamayut', "Sharky's", 'ဓမ္မစေတီလမ်း အမှတ် ၁၁၇', 'ဓမ္မစေတီလမ်းက အချိုပွဲ၊ မုန့်ဖုတ်နဲ့ ထမင်းဟင်းဆိုင်။'],
    ['fine_dining', 'Kamayut', 'Green Elephant', 'ပြည်လမ်း အမှတ် ၅၁၉ အေ၊ သီရိမင်္ဂလာလမ်း', 'ပြည်လမ်းပေါ်က မြန်မာဧည့်ခံဆိုင်။'],
    ['fine_dining', 'Bahan', 'Acacia', 'ဆရာစံလမ်း အမှတ် ၅၂', 'ကိုလိုနီအိမ်ဟောင်းထဲက ညစာဆိုင်။'],
    ['fine_dining', 'Dagon', 'Padonmar', 'ခရေပင်လမ်း အမှတ် ၁၀၅/၁၀၇', 'ဒဂုံက မြန်မာညစာဆိုင်။'],
    ['fine_dining', 'Kyauktada', 'Gekko', 'ကုန်သည်လမ်း အမှတ် ၅၃၅', 'ကိုလိုနီအဆောက်အအုံထဲက ကော့တေးနဲ့ ညစာဆိုင်။'],
    ['fine_dining', 'Kyauktada', 'The Strand Restaurant', 'ကမ်းနားလမ်း အမှတ် ၉၂၊ The Strand', 'Strand ဟိုတယ်ထဲက ညစာဆိုင်။'],

    // Bars
    ['bar', 'Thingangyun', 'Ginki', 'ရတနာလမ်း အမှတ် ၂', 'ရတနာလမ်းက ဘားနဲ့ စားသောက်ဆိုင်။'],
    ['bar', 'Bahan', 'Vista Bar', 'ရွှေဂုံတိုင်အနောက်လမ်း အမှတ် ၁၆၈', 'ရွှေတိဂုံမြင်ကွင်းရှိတဲ့ အပေါ်ဆုံးထပ်ဘား။'],
    ['bar', 'Sanchaung', 'The Penthouse', 'ဘဂရာလမ်း အမှတ် ၂၇၁-၂၇၃၊ Parkside One အထပ် ၈', 'မြေနီကုန်း အပေါ်ဆုံးထပ် ဘား။'],
    ['bar', 'Botataung', 'The 7th Joint', 'မဟာဗန္ဓုလလမ်းနဲ့ ၄၈ လမ်းထောင့်', 'ဂီတတီးလေ့ရှိတဲ့ ဘား။'],
    ['bar', 'Botataung', '50th Street Bar', '၅၀ လမ်း အမှတ် ၉-၁၃', 'ကိုလိုနီအဆောက်အအုံထဲက ဘား။'],
    ['bar', 'Kyauktada', 'Yangon Yangon', 'ဆာကူရာတာဝါ၊ ဗိုလ်ချုပ်အောင်ဆန်းလမ်း အမှတ် ၃၃၉', 'မြို့လယ်တာဝါအပေါ်ထပ်။ မြို့မြင်ကွင်းကြည့်နိုင်သည်။'],
    ['bar', 'Kyauktada', 'Gekko', 'ကုန်သည်လမ်း အမှတ် ၅၃၅', 'ကုန်သည်လမ်းက ကော့တေးဘား။'],
    ['bar', 'Kyauktada', 'Blind Tiger', 'စိတ်ကန္တာလမ်း အမှတ် ၉၃/၉၅', 'မြို့လယ် အောက်ဘလောက်က ဘား။'],
    ['bar', 'Kyauktada', 'Strand Bar', 'ကမ်းနားလမ်း အမှတ် ၉၂၊ The Strand', 'Strand ဟိုတယ်ထဲက ဘား။'],

    // Karaoke
    ['karaoke', 'Thingangyun', 'Seven Sea KTV', 'ဝေဇယန္တာလမ်း၊ ရိပ်သာ ၂ လမ်းနဲ့ ၃ လမ်းကြား', 'ဝေဇယန္တာလမ်းက ကာရာအိုကေ။'],
    ['karaoke', 'Thingangyun', '3 King KTV', 'လေးဒဂုံလမ်း', 'လေးဒဂုံလမ်းက ကာရာအိုကေ။'],
    ['karaoke', 'Thingangyun', 'Joker KTV', 'သံသုမာရလမ်း', 'သံသုမာရလမ်းက ကာရာအိုကေ။'],
    ['karaoke', 'Bahan', 'Relax Karaoke', 'ဓမ္မစေတီလမ်း အမှတ် ၁၂၆', 'ဓမ္မစေတီလမ်းက ကာရာအိုကေ။'],
    ['karaoke', 'Bahan', 'M3 Karaoke', 'ရွှေဂုံတိုင်အနောက်လမ်း၊ ကစားကွင်းအနီး', 'ရွှေဂုံတိုင်အနောက်ဘက်က ကာရာအိုကေ။'],
    ['karaoke', 'South Okkalapa', 'Carnival KTV', 'ဝေဇယန္တာလမ်း အမှတ် ၄၇/၄၈၊ ရပ်ကွက် ၈', 'တောင်ဥက္ကလာပ ဝေဇယန္တာလမ်းက ကာရာအိုကေ။'],

    // Movies
    ['movie', 'Thingangyun', 'Starlight Glory', 'ကျန်စစ်သားလမ်း', 'ကျန်စစ်သားလမ်းက ရုပ်ရှင်ရုံ။'],
    ['movie', 'Kamayut', 'J Cineplex Junction Square', 'Junction Square ပထမထပ်၊ ပြည်လမ်းနဲ့ ကြည်ဦးတော်လမ်းကြား', 'ကြည့်မြင်တိုင်အနီး ရုပ်ရှင်ရုံ။'],
    ['movie', 'Lanmadaw', 'J Cineplex City Mall St. John', 'City Mall St. John တတိယထပ်၊ ပြည်လမ်းနဲ့ မင်းရဲကျော်စွာလမ်းထောင့်', 'လမ်းမတော်က ရုပ်ရှင်ရုံ။'],
    ['movie', 'Lanmadaw', 'J Cineplex Junction Mawtin', 'Junction Mawtin စတုတ္ထထပ်၊ အနော်ရထာလမ်းနဲ့ လမ်းသစ်လမ်းထောင့်', 'လမ်းမတော် Junction Mawtin ထဲက ရုပ်ရှင်ရုံ။'],
    ['movie', 'Pabedan', 'J Cineplex Junction City', 'Junction City အထပ် ၅၊ ဗိုလ်ချုပ်အောင်ဆန်းလမ်းနဲ့ ၂၇ လမ်းထောင့်', 'မြို့လယ် Junction City ရုပ်ရှင်ရုံ။'],
    ['movie', 'Dagon', 'Thamada Cinema', 'အလံပြဘုရားလမ်း အမှတ် ၅', 'အလံပြဘုရားလမ်းက ဓမ္မသ ရုပ်ရှင်ရုံ။'],

    // Shows
    ['show', 'Mingalar Taung Nyunt', 'Karaweik Palace', 'ကန်တော်ကြီး အရှေ့ဘက်ကမ်း', 'ညပိုင်း မြန်မာယဉ်ကျေးမှုအကပြ ကျင်းပတဲ့ နေရာ။'],
    ['show', 'Dagon', 'National Theatre', 'မျိုးမကွန်းလမ်း', 'ဇာတ်ပွဲနဲ့ ဖျော်ဖြေပွဲခန်းမ။'],

    // Zoo
    ['zoo', 'Mingalar Taung Nyunt', 'Yangon Zoological Gardens', 'ကန်ရိပ်သာလမ်း၊ ကန်တော်ကြီးဘေး', 'မြို့တွင်း တိရစ္ဆာန်ဥယျာဉ်။ နေ့ခင်းဖွင့်သည်။'],
    ['zoo', 'Mingaladon', 'Hlawga Wildlife Park', 'ထောက်ကြံ့–လှော်ကားလမ်း၊ ရန်ကုန်-ပြည်လမ်းမဘေး', 'မြို့မြောက်ဘက် မိုင် ၂၀ ခန့်။ ဆာဖာရီနဲ့ တောရိုင်းတိရစ္ဆာန်ဥယျာဉ်။'],

    // Staycation
    ['staycation', 'Tamwe', 'Chatrium Hotel Royal Lake', 'နတ်မောက်လမ်း အမှတ် ၄၀', 'ကန်တော်ကြီးဘေး ဟိုတယ်။'],
    ['staycation', 'Bahan', 'Savoy Hotel', 'ဓမ္မစေတီလမ်း အမှတ် ၁၂၉', 'ဓမ္မစေတီလမ်းက ဟိုတယ်။'],
    ['staycation', 'Yankin', 'Sedona Hotel Yangon', 'ကမ္ဘာအေးဘုရားလမ်း အမှတ် ၁', 'ကမ္ဘာအေးဘုရားလမ်းက ဟိုတယ်။'],
    ['staycation', 'Dagon', "Governor's Residence", 'တော်ဝင်လမ်း အမှတ် ၃၅', 'ကိုလိုနီအိမ်ဟောင်း ဟိုတယ်။'],
    ['staycation', 'Kyauktada', 'The Strand', 'ကမ်းနားလမ်း အမှတ် ၉၂', 'ကမ်းနားလမ်း ကိုလိုနီဟိုတယ်။'],
    ['staycation', 'Kyauktada', 'Rosewood Yangon', 'ကမ်းနားလမ်း အမှတ် ၁၄', 'တရားရုံးဟောင်းအဆောက်အအုံထဲက ဟိုတယ်။'],

    // Camp — inside Yangon city there are few camps; these are the north-of-city park sites.
    ['camp', 'Mingaladon', 'Hlawga Park Camping', 'လှော်ကားပန်းခြံ၊ Resort Area IV၊ ထောက်ကြံ့–လှော်ကားလမ်း', 'မြို့မြောက်ဘက် စခန်းချအစီအစဉ်။ အုပ်စုလိုက် မှာနိုင်သည်။'],
    ['camp', 'Mingaladon', 'Hlawga Eco-lodges', 'လှော်ကားပန်းခြံ၊ Picnic Site 2', 'နေ့ခင်းအပန်းဖြေအိမ်ငယ်။ ဝင်ချိန် မနက် ၈ နာရီ၊ ထွက်ချိန် ညနေ ၄ နာရီခွဲ။'],
];

$rows = [];
foreach ($raw as $item) {
    [$style, $township, $name, $address, $detail] = $item;
    if (!in_array($style, $styles, true)) {
        throw new RuntimeException('Unknown meetup style: ' . $style);
    }
    if (!isset($townships[$township])) {
        throw new RuntimeException('Unknown township: ' . $township);
    }
    $meta = $townships[$township];
    $rows[] = [
        'city' => 'Yangon',
        'city_mm' => 'ရန်ကုန်',
        'township' => $township,
        'township_mm' => $meta['mm'],
        'township_sort' => $meta['sort'],
        'meetup_style' => $style,
        'name' => $name,
        'address' => $address,
        'detail' => $detail,
    ];
}

return $rows;
