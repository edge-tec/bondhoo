package com.connectly.android.feature.auth.presentation.register

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import com.connectly.android.core.designsystem.theme.ConnectlyBlue

data class Country(
    val name: String,
    val code: String,
    val dialCode: String,
    val flag: String
)

object CountryData {
    val DEFAULT = Country("Bangladesh", "BD", "+880", "🇧🇩")

    val ALL_COUNTRIES = listOf(
        Country("Bangladesh", "BD", "+880", "🇧🇩"),
        Country("Saudi Arabia", "SA", "+966", "🇸🇦"),
        Country("United Arab Emirates", "AE", "+971", "🇦🇪"),
        Country("United States", "US", "+1", "🇺🇸"),
        Country("United Kingdom", "GB", "+44", "🇬🇧"),
        Country("India", "IN", "+91", "🇮🇳"),
        Country("Pakistan", "PK", "+92", "🇵🇰"),
        Country("Canada", "CA", "+1", "🇨🇦"),
        Country("Australia", "AU", "+61", "🇦🇺"),
        Country("Malaysia", "MY", "+60", "🇲🇾"),
        Country("Singapore", "SG", "+65", "🇸🇬"),
        Country("Qatar", "QA", "+974", "🇶🇦"),
        Country("Kuwait", "KW", "+965", "🇰🇼"),
        Country("Oman", "OM", "+968", "🇴🇲"),
        Country("Bahrain", "BH", "+973", "🇧🇭"),
        Country("Germany", "DE", "+49", "🇩🇪"),
        Country("France", "FR", "+33", "🇫🇷"),
        Country("Italy", "IT", "+39", "🇮🇹"),
        Country("Spain", "ES", "+34", "🇪🇸"),
        Country("Turkey", "TR", "+90", "🇹🇷"),
        Country("Japan", "JP", "+81", "🇯🇵"),
        Country("South Korea", "KR", "+82", "🇰🇷"),
        Country("China", "CN", "+86", "🇨🇳"),
        Country("Indonesia", "ID", "+62", "🇮🇩"),
        Country("Egypt", "EG", "+20", "🇪🇬"),
        Country("South Africa", "ZA", "+27", "🇿🇦"),
        Country("Nigeria", "NG", "+234", "🇳🇬"),
        Country("Brazil", "BR", "+55", "🇧🇷"),
        Country("Mexico", "MX", "+52", "🇲🇽"),
        Country("Russia", "RU", "+7", "🇷🇺"),
        Country("Netherlands", "NL", "+31", "🇳🇱"),
        Country("Sweden", "SE", "+46", "🇸🇪"),
        Country("Switzerland", "CH", "+41", "🇨🇭"),
        Country("Norway", "NO", "+47", "🇳🇴"),
        Country("Denmark", "DK", "+45", "🇩🇰"),
        Country("Finland", "FI", "+358", "🇫🇮"),
        Country("Ireland", "IE", "+353", "🇮🇪"),
        Country("New Zealand", "NZ", "+64", "🇳🇿"),
        Country("Philippines", "PH", "+63", "🇵🇭"),
        Country("Thailand", "TH", "+66", "🇹🇭"),
        Country("Vietnam", "VN", "+84", "🇻🇳"),
        Country("Nepal", "NP", "+977", "🇳🇵"),
        Country("Sri Lanka", "LK", "+94", "🇱🇰"),
        Country("Maldives", "MV", "+960", "🇲🇻"),
        Country("Jordan", "JO", "+962", "🇯🇴"),
        Country("Lebanon", "LB", "+961", "🇱🇧"),
        Country("Morocco", "MA", "+212", "🇲🇦"),
        Country("Kenya", "KE", "+254", "🇰🇪"),
        Country("Ghana", "GH", "+233", "🇬🇭"),
        Country("Argentina", "AR", "+54", "🇦🇷"),
        Country("Colombia", "CO", "+57", "🇨🇴"),
        Country("Chile", "CL", "+56", "🇨🇱"),
        Country("Poland", "PL", "+48", "🇵🇱"),
        Country("Portugal", "PT", "+351", "🇵🇹"),
        Country("Greece", "GR", "+30", "🇬🇷"),
        Country("Austria", "AT", "+43", "🇦🇹"),
        Country("Belgium", "BE", "+32", "🇧🇪")
    ).sortedBy { it.name }
}

@Composable
fun CountryPickerDialog(
    onDismissRequest: () -> Unit,
    onCountrySelected: (Country) -> Unit
) {
    var searchQuery by remember { mutableStateOf("") }
    val filteredCountries = remember(searchQuery) {
        if (searchQuery.isBlank()) {
            CountryData.ALL_COUNTRIES
        } else {
            val q = searchQuery.trim().lowercase()
            CountryData.ALL_COUNTRIES.filter {
                it.name.lowercase().contains(q) ||
                it.dialCode.contains(q) ||
                it.code.lowercase().contains(q)
            }
        }
    }

    Dialog(onDismissRequest = onDismissRequest) {
        Surface(
            shape = RoundedCornerShape(20.dp),
            color = MaterialTheme.colorScheme.surface,
            tonalElevation = 6.dp,
            modifier = Modifier
                .fillMaxWidth()
                .fillMaxHeight(0.82f)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(20.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Select Country",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    IconButton(onClick = onDismissRequest) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

                OutlinedTextField(
                    value = searchQuery,
                    onValueChange = { searchQuery = it },
                    placeholder = { Text("Search country or dial code...") },
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = ConnectlyBlue) },
                    singleLine = true,
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.fillMaxWidth()
                )

                Spacer(modifier = Modifier.height(14.dp))

                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    items(filteredCountries, key = { it.code + it.dialCode }) { country ->
                        Surface(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable {
                                    onCountrySelected(country)
                                    onDismissRequest()
                                },
                            shape = RoundedCornerShape(10.dp),
                            color = MaterialTheme.colorScheme.surface
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(horizontal = 12.dp, vertical = 12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = country.flag,
                                    fontSize = 24.sp,
                                    modifier = Modifier.padding(end = 12.dp)
                                )
                                Text(
                                    text = country.name,
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Medium,
                                    color = MaterialTheme.colorScheme.onSurface,
                                    modifier = Modifier.weight(1f)
                                )
                                Text(
                                    text = country.dialCode,
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = ConnectlyBlue
                                )
                            }
                        }
                        HorizontalDivider(
                            color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f),
                            thickness = 0.5.dp
                        )
                    }
                }
            }
        }
    }
}

